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
			$query = Category::where("user_id", $user->id);

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
	 * Create new category
	 */
	public function createCategory(array $data, User $user): Category
	{
		// Set user_id and default icon if not provided
		$data["user_id"] = $user->id;

		// If icon is not provided, try to get from default icons based on name
		if (!isset($data["icon"]) && isset($data["name"])) {
			$data["icon"] = Category::getDefaultIcon(
				$data["name"],
				$data["type"] ?? CategoryType::EXPENSE
			);
		}

		// Invalidate cache
		$this->invalidateUserCategoryCache($user->id);

		return Category::create($data);
	}

	/**
	 * Update category
	 */
	public function updateCategory(Category $category, array $data): Category
	{
		// If name is changed and icon is default, update icon too
		if (isset($data["name"]) && !isset($data["icon"])) {
			$currentIcon = $category->icon;
			$defaultIcons = Category::DEFAULT_ICONS[$category->type] ?? [];
			$isDefaultIcon = in_array($currentIcon, array_values($defaultIcons));

			if ($isDefaultIcon) {
				$data["icon"] = Category::getDefaultIcon(
					$data["name"],
					$category->type
				);
			}
		}

		// Invalidate cache
		$this->invalidateUserCategoryCache($category->user_id);

		$category->update($data);
		return $category->fresh();
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
			return Category::where("user_id", $user->id)
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

		$cacheKey = "categories_monthly_totals_{$user->id}_{$month}_{$year}";

		return Cache::remember($cacheKey, self::CACHE_DURATION, function () use (
			$user,
			$month,
			$year
		) {
			// Single query dengan subquery untuk monthly totals
			return Category::where("user_id", $user->id)
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
				->map(function ($category) use ($month, $year) {
					$monthlyTotal = $category->monthly_total;
					$category->monthly_total = $monthlyTotal;

					// Get active budget for the period
					$activeBudget = $category->getActiveBudget($month, $year);
					if ($activeBudget) {
						$category->budget_usage =
							$activeBudget->amount > 0
								? ($monthlyTotal / $activeBudget->amount) * 100
								: 0;
						$category->has_budget_exceeded =
							$monthlyTotal > $activeBudget->amount;
					} else {
						$category->budget_usage = 0;
						$category->has_budget_exceeded = false;
					}

					return $category;
				});
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
			$query = Category::where("user_id", $user->id)->where("is_active", true);

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
	 * Get category usage statistics (optimized)
	 */
	public function getCategoryUsage(
		Category $category,
		?string $startDate = null,
		?string $endDate = null
	): array {
		$cacheKey = "category_usage_{$category->id}_{$startDate}_{$endDate}";

		return Cache::remember($cacheKey, self::CACHE_DURATION, function () use (
			$category,
			$startDate,
			$endDate
		) {
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

			// Get current active budget
			$activeBudget = $category->getActiveBudget();
			$budgetUsage =
				$activeBudget && $activeBudget->amount > 0
					? ($total / $activeBudget->amount) * 100
					: null;

			// Monthly total
			$monthlyTotal = $category->getExpenseTotal();

			return [
				"category" => $category,
				"total_amount" => $total,
				"transaction_count" => $count,
				"budget_usage" => $budgetUsage,
				"budget_exceeded" => $activeBudget && $total > $activeBudget->amount,
				"average_transaction" => $count > 0 ? $total / $count : 0,
				"monthly_total" => $monthlyTotal,
				"budget_usage_percentage" => $activeBudget
					? $activeBudget->percentage
					: 0,
				"formatted_budget_limit" => $activeBudget
					? $activeBudget->formatted_amount
					: "Rp 0",
			];
		});
	}

	/**
	 * Get all categories usage statistics (optimized with single query)
	 */
	public function getAllCategoriesUsage(
		User $user,
		?string $startDate = null,
		?string $endDate = null
	): Collection {
		$cacheKey = "all_categories_usage_{$user->id}_{$startDate}_{$endDate}";

		return Cache::remember($cacheKey, self::CACHE_DURATION, function () use (
			$user,
			$startDate,
			$endDate
		) {
			// Gunakan withCount dan withSum untuk optimasi
			$query = Category::where(function ($q) use ($user) {
				$q->where("user_id", $user->id)->orWhereNull("user_id");
			});

			if ($startDate || $endDate) {
				$query->withCount([
					"transactions" => function ($query) use ($startDate, $endDate) {
						if ($startDate) {
							$query->whereDate("transaction_date", ">=", $startDate);
						}
						if ($endDate) {
							$query->whereDate("transaction_date", "<=", $endDate);
						}
					},
				]);

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
			} else {
				$query->withCount("transactions")->withSum("transactions", "amount");
			}

			return $query->get()->map(function ($category) {
				$total = $category->transactions_sum_amount ?? 0;
				$activeBudget = $category->getActiveBudget();

				return [
					"category" => $category,
					"total_amount" => $total,
					"transaction_count" => $category->transactions_count,
					"budget_usage" =>
						$activeBudget && $activeBudget->amount > 0
							? ($total / $activeBudget->amount) * 100
							: null,
					"budget_exceeded" => $activeBudget && $total > $activeBudget->amount,
					"budget_usage_percentage" => $activeBudget
						? $activeBudget->percentage
						: 0,
					"formatted_budget_limit" => $activeBudget
						? $activeBudget->formatted_amount
						: "Rp 0",
				];
			});
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

		return $category->fresh();
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
			$stats = Category::where("user_id", $user->id)
				->selectRaw(
					"
                    COUNT(*) as total,
                    SUM(CASE WHEN type = 'income' AND is_active = 1 THEN 1 ELSE 0 END) as income,
                    SUM(CASE WHEN type = 'expense' AND is_active = 1 THEN 1 ELSE 0 END) as expense,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active
                "
				)
				->first();

			// Hitung kategori dengan budget aktif bulan ini
			$currentMonth = date("m");
			$currentYear = date("Y");

			$categoriesWithBudget = Category::where("user_id", $user->id)
				->expense()
				->active()
				->whereHas("budgets", function ($query) use (
					$currentMonth,
					$currentYear
				) {
					$query
						->where("month", $currentMonth)
						->where("year", $currentYear)
						->where("is_active", true);
				})
				->count();

			// Hitung budget exceeded bulan ini
			$budgetExceededCount = 0;
			if ($categoriesWithBudget > 0) {
				$budgetExceededCount = Category::where("user_id", $user->id)
					->expense()
					->active()
					->whereHas("budgets", function ($query) use (
						$currentMonth,
						$currentYear
					) {
						$query
							->where("month", $currentMonth)
							->where("year", $currentYear)
							->where("is_active", true)
							->whereRaw("budgets.spent > budgets.amount");
					})
					->count();
			}

			return [
				"total" => $stats->total ?? 0,
				"income" => $stats->income ?? 0,
				"expense" => $stats->expense ?? 0,
				"active" => $stats->active ?? 0,
				"with_budget" => $categoriesWithBudget,
				"budget_exceeded" => $budgetExceededCount,
			];
		});
	}

	/**
	 * Get categories with budget warnings (optimized)
	 */
	public function getBudgetWarnings(User $user, int $threshold = 80): Collection
	{
		$currentMonth = date("m");
		$currentYear = date("Y");

		$cacheKey = "budget_warnings_{$user->id}_{$currentMonth}_{$currentYear}_{$threshold}";

		return Cache::remember(
			$cacheKey,
			self::CACHE_DURATION / 2,
			function () use ($user, $currentMonth, $currentYear, $threshold) {
				// Single query dengan subquery untuk monthly total dan budget
				$categories = Category::where("user_id", $user->id)
					->expense()
					->active()
					->whereHas("budgets", function ($query) use (
						$currentMonth,
						$currentYear
					) {
						$query
							->where("month", $currentMonth)
							->where("year", $currentYear)
							->where("is_active", true);
					})
					->with([
						"budgets" => function ($query) use ($currentMonth, $currentYear) {
							$query
								->where("month", $currentMonth)
								->where("year", $currentYear);
						},
					])
					->get();

				// Tambahkan monthly total dan filter berdasarkan threshold
				return $categories
					->map(function ($category) use ($currentMonth, $currentYear) {
						$budget = $category->budgets->first();
						$monthlyTotal = $category->getExpenseTotal(
							$currentMonth,
							$currentYear
						);
						$usage =
							$budget && $budget->amount->getAmount()->toInt() > 0
								? ($monthlyTotal / $budget->amount->getAmount()->toInt()) * 100
								: 0;

						return [
							"category" => $category,
							"usage_percentage" => $usage,
							"monthly_total" => $this->formatMoney(
								$this->fromDatabaseAmount($monthlyTotal)
							),
							"budget_limit" => $budget
								? $budget->amount->getAmount()->toInt()
								: 0,
							"formatted_budget_limit" => $budget
								? $budget->formatted_amount
								: "Rp 0",
							"is_exceeded" =>
								$budget &&
								$monthlyTotal > $budget->amount->getAmount()->toInt(),
						];
					})
					->filter(fn($item) => $item["usage_percentage"] >= $threshold);
			}
		);
	}

	/**
	 * Search categories by name
	 */
	public function search(
		string $search,
		User $user,
		string $type = null
	): Collection {
		$query = Category::where("user_id", $user->id)->where("is_active", true);

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
	 * Get expense categories without budget for current period
	 */
	public function getUnbudgetedCategories(
		User $user,
		int $month = null,
		int $year = null
	): Collection {
		$month = $month ?? date("m");
		$year = $year ?? date("Y");

		$cacheKey = "unbudgeted_categories_{$user->id}_{$month}_{$year}";

		return Cache::remember($cacheKey, self::CACHE_DURATION, function () use (
			$user,
			$month,
			$year
		) {
			return Category::expense()
				->forUser($user->id)
				->active()
				->whereDoesntHave("budgets", function ($query) use ($month, $year) {
					$query
						->where("month", $month)
						->where("year", $year)
						->where("is_active", true);
				})
				->get();
		});
	}

	/**
	 * Get popular categories (most used)
	 */
	public function getPopularCategories(
		User $user,
		int $limit = 5,
		string $type = null
	): Collection {
		$cacheKey = "popular_categories_{$user->id}_{$limit}_{$type}";

		return Cache::remember($cacheKey, self::CACHE_DURATION, function () use (
			$user,
			$limit,
			$type
		) {
			$query = Category::where("user_id", $user->id)
				->withCount("transactions")
				->orderBy("transactions_count", "desc")
				->limit($limit);

			if ($type) {
				$query->where("type", $type);
			}

			return $query->get();
		});
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
			"categories_monthly_totals_{$userId}_*",
			"category_usage_{$userId}_*",
			"all_categories_usage_{$userId}_*",
			"budget_warnings_{$userId}_*",
			"unbudgeted_categories_{$userId}_*",
			"popular_categories_{$userId}_*",
		];

		foreach ($patterns as $pattern) {
			$this->clearCacheByPattern($pattern);
		}
	}

	/**
	 * Clear cache by pattern
	 */
	private function clearCacheByPattern(string $pattern): void
	{
		// Untuk driver yang support tags
		if (method_exists(Cache::store(), "tags")) {
			Cache::tags(["categories"])->flush();
		} else {
			// Fallback sederhana
			$prefix = config("cache.prefix");
			$pattern = str_replace("*", ".*", $pattern);

			// Hanya contoh, implementasi nyata tergantung cache driver
			// Untuk production, pertimbangkan menggunakan Redis/Memcached dengan tags
			$keys = Cache::getStore()->getKeys();
			foreach ($keys as $key) {
				if (preg_match("/{$pattern}/", $key)) {
					Cache::forget($key);
				}
			}
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
