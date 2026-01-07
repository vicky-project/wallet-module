<?php

namespace Modules\Wallet\Repositories;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Wallet\Models\Category;
use Modules\Wallet\Enums\CategoryType;
use Modules\Wallet\Enums\TransactionType;
use Illuminate\Database\Eloquent\Builder;

class CategoryRepository extends BaseRepository
{
	private const CACHE_DURATION = 3600; // 1 jam

	public function __construct(Category $model)
	{
		$this->model = $model;
	}

	/**
	 * Get all categories for current user with filters (cached)
	 */
	public function getUserCategories(
		string $type = null,
		bool $includeInactive = false
	): Collection {
		$user = auth()->user();
		$cacheKey = "user_categories_{$user->id}_{$type}_{$includeInactive}";

		return Cache::remember($cacheKey, self::CACHE_DURATION, function () use (
			$user,
			$type,
			$includeInactive
		) {
			$query = $this->model->where("user_id", $user->id);

			if ($type) {
				$query->where("type", $type);
			}

			if (!$includeInactive) {
				$query->where("is_active", true);
			}

			return $query->orderBy("name")->get();
		});
	}

	/**
	 * Create new category with budget limit
	 */
	public function createCategory(array $data, User $user): Category
	{
		if (isset($data["budget_limit"])) {
			$data["budget_limit"] = $this->toDatabaseAmount(
				$this->toMoney($data["budget_limit"] ?: 0)
			);
		}

		$data["user_id"] = $user->id;

		if (!isset($data["icon"]) && isset($data["name"])) {
			$data["icon"] = $this->getDefaultIcon(
				$data["name"],
				$data["type"] ?? CategoryType::EXPENSE
			);
		}

		// Invalidate cache
		$this->invalidateUserCategoryCache($user->id);

		return $this->create($data);
	}

	/**
	 * Update category with budget limit
	 */
	public function updateCategory(Category $category, array $data): Category
	{
		if (isset($data["budget_limit"])) {
			$data["budget_limit"] = $this->toDatabaseAmount(
				$this->toMoney($data["budget_limit"])
			);
		}

		if (isset($data["name"]) && !isset($data["icon"])) {
			$currentIcon = $category->icon;
			$defaultIcons = Category::DEFAULT_ICONS[$category->type] ?? [];
			$isDefaultIcon = in_array($currentIcon, array_values($defaultIcons));

			if ($isDefaultIcon) {
				$data["icon"] = $this->getDefaultIcon($data["name"], $category->type);
			}
		}

		// Invalidate cache
		$this->invalidateUserCategoryCache($category->user_id);

		$this->update($category->id, $data);
		return $this->find($category->id);
	}

	/**
	 * Delete category with validation
	 */
	public function deleteCategory(Category $category): bool
	{
		// Check if category has transactions
		if ($category->transactions()->exists()) {
			throw new \Exception("Cannot delete category that has transactions");
		}

		// Check if category has budgets
		if ($category->budgets()->exists()) {
			throw new \Exception("Cannot delete category that has budgets");
		}

		// Invalidate cache
		$this->invalidateUserCategoryCache($category->user_id);

		return $category->delete();
	}

	/**
	 * Get categories by type (cached)
	 */
	public function getByType(string $type, User $user): Collection
	{
		$cacheKey = "user_categories_by_type_{$user->id}_{$type}";

		return Cache::remember($cacheKey, self::CACHE_DURATION, function () use (
			$user,
			$type
		) {
			return $this->model
				->where("user_id", $user->id)
				->where("type", $type)
				->where("is_active", true)
				->orderBy("name")
				->get();
		});
	}

	/**
	 * Get categories with monthly totals (optimized with single query)
	 */
	public function getWithMonthlyTotals(
		User $user,
		int $month = null,
		int $year = null
	): Collection {
		$month = $month ?? date("m");
		$year = $year ?? date("Y");

		// Single query with subquery for monthly totals
		return $this->model
			->where("user_id", $user->id)
			->where("type", CategoryType::EXPENSE)
			->select(["*"])
			->addSelect([
				"monthly_total" => DB::table("transactions")
					->selectRaw("COALESCE(SUM(amount), 0)")
					->whereColumn("category_id", "categories.id")
					->where("user_id", $user->id)
					->where("type", TransactionType::EXPENSE)
					->whereMonth("transaction_date", $month)
					->whereYear("transaction_date", $year),
			])
			->get()
			->map(function ($category) {
				$monthlyTotal = $category->monthly_total;
				$category->monthly_total = $monthlyTotal;
				$category->budget_usage = $category->budget_limit
					? ($monthlyTotal / $category->budget_limit) * 100
					: 0;
				$category->has_budget_exceeded =
					$category->budget_limit && $monthlyTotal > $category->budget_limit;
				return $category;
			});
	}

	/**
	 * Get categories for dropdown (cached)
	 */
	public function getForDropdown(User $user, string $type = null): array
	{
		$cacheKey = "categories_dropdown_{$user->id}_{$type}";

		return Cache::remember($cacheKey, self::CACHE_DURATION, function () use (
			$user,
			$type
		) {
			$query = $this->model
				->where("user_id", $user->id)
				->where("is_active", true);

			if ($type) {
				$query->where("type", $type);
			}

			return $query
				->orderBy("type")
				->orderBy("name")
				->get()
				->mapWithKeys(function ($category) {
					$typeLabel =
						$category->type === CategoryType::INCOME
							? "Pemasukan"
							: "Pengeluaran";
					return [
						$category->id => "[{$typeLabel}] {$category->name}",
					];
				})
				->toArray();
		});
	}

	/**
	 * Reorder categories (batch update)
	 */
	public function reorderCategories(array $categories): void
	{
		$cases = [];
		$ids = [];
		$params = [];

		foreach ($categories as $index => $item) {
			$id = (int) $item["id"];
			$order = (int) $item["order"];

			$cases[] = "WHEN id = ? THEN ?";
			$params[] = $id;
			$params[] = $order;
			$ids[] = $id;
		}

		if (empty($cases)) {
			return;
		}

		$idsStr = implode(",", $ids);
		$casesStr = implode(" ", $cases);

		DB::update(
			"UPDATE categories SET `order` = CASE {$casesStr} END WHERE id IN ({$idsStr})",
			$params
		);
	}

	/**
	 * Get category usage statistics (optimized)
	 */
	public function getCategoryUsage(
		Category $category,
		?string $startDate = null,
		?string $endDate = null
	): array {
		$query = $category->transactions();

		if ($startDate) {
			$query->whereDate("transaction_date", ">=", $startDate);
		}

		if ($endDate) {
			$query->whereDate("transaction_date", "<=", $endDate);
		}

		// Single query untuk semua aggregasi
		$stats = $query
			->select([
				DB::raw("COALESCE(SUM(amount), 0) as total_amount"),
				DB::raw("COUNT(*) as transaction_count"),
			])
			->first();

		$total = $stats->total_amount ?? 0;
		$count = $stats->transaction_count ?? 0;
		$budgetLimit = $category->budget_limit
			? $category->budget_limit->getAmount()->toInt()
			: 0;
		$budgetUsage = $budgetLimit > 0 ? ($total / $budgetLimit) * 100 : null;

		// Gunakan cached monthly total dari scope
		$monthlyTotal = $category->getCachedMonthlyTotal();

		return [
			"category" => $category,
			"total_amount" => $total,
			"transaction_count" => $count,
			"budget_usage" => $budgetUsage,
			"budget_exceeded" => $budgetLimit > 0 && $total > $budgetLimit,
			"average_transaction" => $count > 0 ? $total / $count : 0,
			"monthly_total" => $monthlyTotal,
			"budget_usage_percentage" => $category->budget_usage_percentage,
			"formatted_budget_limit" => $category->formatted_budget_limit,
		];
	}

	/**
	 * Get all categories usage statistics (optimized with single query)
	 */
	public function getAllCategoriesUsage(
		?string $startDate = null,
		?string $endDate = null
	): Collection {
		$query = $this->model->withCount([
			"transactions" => function ($query) use ($startDate, $endDate) {
				if ($startDate) {
					$query->whereDate("transaction_date", ">=", $startDate);
				}
				if ($endDate) {
					$query->whereDate("transaction_date", "<=", $endDate);
				}
			},
		]);

		// Subquery untuk total amount
		$query->withSum(
			[
				"transactions" => function ($query) use ($startDate, $endDate) {
					if ($startDate) {
						$query->whereDate("transaction_date", ">=", $startDate);
					}
					if ($endDate) {
						$query->whereDate("transaction_date", "<=", $endDate);
					}
				},
			],
			"amount"
		);

		return $query->get()->map(function ($category) {
			$total = $category->transactions_sum_amount ?? 0;
			$budgetLimit = $category->budget_limit ?? 0;

			return [
				"category" => $category,
				"total_amount" => $total,
				"transaction_count" => $category->transactions_count,
				"budget_usage" =>
					$budgetLimit > 0 ? ($total / $budgetLimit) * 100 : null,
				"budget_exceeded" => $budgetLimit > 0 && $total > $budgetLimit,
				"budget_usage_percentage" => $category->budget_usage_percentage,
				"formatted_budget_limit" => $category->formatted_budget_limit,
			];
		});
	}

	/**
	 * Toggle category active status
	 */
	public function toggleStatus(Category $category): Category
	{
		$category->is_active = !$category->is_active;
		$category->save();

		// Invalidate cache
		$this->invalidateUserCategoryCache($category->user_id);

		return $category;
	}

	/**
	 * Get category statistics for dashboard (optimized with single query)
	 */
	public function getCategoryStats(User $user): array
	{
		$cacheKey = "category_stats_{$user->id}";

		return Cache::remember($cacheKey, self::CACHE_DURATION, function () use (
			$user
		) {
			// Single query untuk semua stats
			$stats = $this->model
				->where("user_id", $user->id)
				->selectRaw(
					"
                    COUNT(*) as total,
                    SUM(CASE WHEN type = 'income' AND is_active = 1 THEN 1 ELSE 0 END) as income,
                    SUM(CASE WHEN type = 'expense' AND is_active = 1 THEN 1 ELSE 0 END) as expense,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN type = 'expense' AND is_active = 1 AND budget_limit IS NOT NULL THEN 1 ELSE 0 END) as with_budget
                "
				)
				->first();

			// Hitung budget exceeded (gunakan subquery untuk efisiensi)
			$budgetExceededCount = $this->model
				->where("user_id", $user->id)
				->where("type", "expense")
				->where("is_active", true)
				->whereNotNull("budget_limit")
				->whereHas("transactions", function ($query) {
					$query
						->select(DB::raw("COALESCE(SUM(amount), 0) as total"))
						->whereMonth("transaction_date", now()->month)
						->whereYear("transaction_date", now()->year)
						->havingRaw("total > budget_limit");
				})
				->count();

			return [
				"total" => $stats->total ?? 0,
				"income" => $stats->income ?? 0,
				"expense" => $stats->expense ?? 0,
				"active" => $stats->active ?? 0,
				"with_budget" => $stats->with_budget ?? 0,
				"budget_exceeded" => $budgetExceededCount,
			];
		});
	}

	/**
	 * Get default icon based on category name and type
	 */
	private function getDefaultIcon(string $name, string $type): string
	{
		$lowerName = strtolower($name);
		$defaultIcons = Category::DEFAULT_ICONS[$type] ?? [];

		foreach ($defaultIcons as $key => $icon) {
			if (str_contains($lowerName, $key)) {
				return $icon;
			}
		}

		return $type === CategoryType::INCOME ? "bi-cash-stack" : "bi-wallet2";
	}

	/**
	 * Get categories with budget warnings (optimized)
	 */
	public function getBudgetWarnings(User $user, int $threshold = 80): Collection
	{
		$currentMonth = date("m");
		$currentYear = date("Y");

		// Single query dengan subquery untuk monthly total
		return $this->model
			->where("user_id", $user->id)
			->where("type", "expense")
			->where("is_active", true)
			->whereNotNull("budget_limit")
			->select(["*"])
			->addSelect([
				"current_month_total" => DB::table("transactions")
					->selectRaw("COALESCE(SUM(amount), 0)")
					->whereColumn("category_id", "categories.id")
					->where("user_id", $user->id)
					->where("type", TransactionType::EXPENSE)
					->whereMonth("transaction_date", $currentMonth)
					->whereYear("transaction_date", $currentYear),
			])
			->get()
			->map(function ($category) use ($threshold) {
				$monthlyTotal = $category->current_month_total ?? 0;
				$budgetLimit = $category->budget_limit;
				$usage = $budgetLimit > 0 ? ($monthlyTotal / $budgetLimit) * 100 : 0;

				return [
					"category" => $category,
					"usage_percentage" => $usage,
					"monthly_total" => $monthlyTotal,
					"budget_limit" => $budgetLimit,
					"formatted_budget_limit" => $category->formatted_budget_limit,
					"is_exceeded" => $monthlyTotal > $budgetLimit,
				];
			})
			->filter(fn($item) => $item["usage_percentage"] >= $threshold);
	}

	/**
	 * Search categories by name
	 */
	public function search(
		string $search,
		User $user,
		string $type = null
	): Collection {
		$query = $this->model
			->where("user_id", $user->id)
			->where("is_active", true);

		if ($type) {
			$query->where("type", $type);
		}

		return $query
			->where(function ($q) use ($search) {
				$q->where("name", "LIKE", "%{$search}%")->orWhere(
					"description",
					"LIKE",
					"%{$search}%"
				);
			})
			->orderBy("name")
			->get();
	}

	/**
	 * Invalidate user category cache
	 */
	private function invalidateUserCategoryCache(int $userId): void
	{
		$patterns = [
			"user_categories_{$userId}_*",
			"user_categories_by_type_{$userId}_*",
			"categories_dropdown_{$userId}_*",
			"category_stats_{$userId}",
		];

		foreach ($patterns as $pattern) {
			$this->clearCacheByPattern($pattern);
		}
	}

	/**
	 * Clear cache by pattern (for file-based cache, implementasi sederhana)
	 */
	private function clearCacheByPattern(string $pattern): void
	{
		// Untuk driver cache yang mendukung tags atau pattern
		if (method_exists(Cache::store(), "tags")) {
			// Implementasi berdasarkan environment cache
			Cache::tags(["categories"])->flush();
		} else {
			// Fallback: clear semua cache kategori untuk user tertentu
			Cache::forget($pattern);
		}
	}

	/**
	 * Get combined data for dashboard/index (optimized untuk controller)
	 */
	public function getUserCategoriesWithStats(
		string $type = null,
		bool $includeInactive = false
	): array {
		$user = auth()->user();
		$cacheKey = "user_categories_with_stats_{$user->id}_{$type}_{$includeInactive}";

		return Cache::remember($cacheKey, self::CACHE_DURATION, function () use (
			$user,
			$type,
			$includeInactive
		) {
			$categories = $this->getUserCategories($type, $includeInactive);
			$stats = $this->getCategoryStats($user);

			return [
				"categories" => $categories,
				"stats" => $stats,
			];
		});
	}
}
