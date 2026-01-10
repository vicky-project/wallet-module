<?php

namespace Modules\Wallet\Repositories;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Modules\Wallet\Models\Category;
use Modules\Wallet\Models\Budget;
use Modules\Wallet\Helpers\Helper;
use Modules\Wallet\Enums\CategoryType;
use Modules\Wallet\Enums\TransactionType;
use Modules\Wallet\Enums\PeriodType;
use Illuminate\Database\Eloquent\Builder;

class CategoryRepository extends BaseRepository
{
	public function __construct(Category $model)
	{
		parent::__construct($model);
	}

	/**
	 * Get all categories for current user with filters (cached)
	 */
	public function getUserCategories(
		string $type = null,
		bool $includeInactive = false
	): Collection {
		$user = auth()->user();
		$cacheKey = Helper::generateCacheKey("user_categories", [
			"user_id" => $user->id,
			"type" => $type,
			"include_inactive" => $includeInactive,
		]);

		return Cache::remember(
			$cacheKey,
			config("wallet.cache_ttl"),
			function () use ($user, $type, $includeInactive) {
				$query = Category::where("user_id", $user->id);

				if ($type) {
					$query->where("type", $type);
				}

				if (!$includeInactive) {
					$query->where("is_active", true);
				}

				return $query->orderBy("name")->get();
			}
		);
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
	public function getByType(string|CategoryType $type, User $user): Collection
	{
		if ($type instanceof CategoryType) {
			$type = $type->value;
		}

		$cacheKey = Helper::generateCacheKey("user_categories_by_type", [
			"user_id" => $user->id,
			"type" => $type,
		]);

		return Cache::remember(
			$cacheKey,
			config("wallet.cache_ttl"),
			function () use ($user, $type) {
				return Category::where("user_id", $user->id)
					->where("type", $type)
					->active()
					->orderBy("name")
					->get();
			}
		);
	}

	/**
	 * Get categories with monthly totals (optimized with single query)
	 * Sekarang menggunakan period_type = monthly
	 */
	public function getWithMonthlyTotals(
		User $user,
		int $month = null,
		int $year = null
	): Collection {
		$month = $month ?? date("m");
		$year = $year ?? date("Y");

		// Dapatkan start_date dan end_date untuk bulan tersebut
		$startDate = Carbon::create($year, $month, 1)->startOfMonth();
		$endDate = Carbon::create($year, $month, 1)->endOfMonth();

		$cacheKey = Helper::generateCacheKey("categories_monthly_totals", [
			"user_id" => $user->id,
			"month" => $month,
			"year" => $year,
		]);

		return Cache::remember(
			$cacheKey,
			config("wallet.cache_ttl"),
			function () use ($user, $month, $year, $startDate, $endDate) {
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
					->map(function ($category) use (
						$user,
						$month,
						$year,
						$startDate,
						$endDate
					) {
						$monthlyTotal = $category->monthly_total;
						$category->monthly_total = $monthlyTotal;

						// Get active budget for the period
						$activeBudget = $this->getActiveBudgetForPeriod(
							$category,
							$user->id,
							PeriodType::MONTHLY,
							$month,
							$year,
							$startDate,
							$endDate
						);

						if ($activeBudget) {
							$category->budget_usage =
								$activeBudget->amount > 0
									? ($monthlyTotal / $activeBudget->amount) * 100
									: 0;
							$category->has_budget_exceeded =
								$monthlyTotal > $activeBudget->amount;
							$category->budget_amount = $activeBudget->amount;
						} else {
							$category->budget_usage = 0;
							$category->has_budget_exceeded = false;
							$category->budget_amount = 0;
						}

						return $category;
					});
			}
		);
	}

	/**
	 * Get categories for dropdown (cached)
	 */
	public function getForDropdown(User $user, string $type = null): array
	{
		$cacheKey = Helper::generateCacheKey("categories_dropdown", [
			"user_id" => $user->id,
			"type" => $type,
		]);

		return Cache::remember(
			$cacheKey,
			config("wallet.cache_ttl"),
			function () use ($user, $type) {
				$query = Category::where("user_id", $user->id)->where(
					"is_active",
					true
				);

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
			}
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
		$cacheKey = Helper::generateCacheKey("category_usage", [
			"category_id" => $category->id,
			"start_date" => $startDate,
			"end_date" => $endDate,
		]);

		return Cache::remember(
			$cacheKey,
			config("wallet.cache_ttl"),
			function () use ($category, $startDate, $endDate) {
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

				// Get current active budget berdasarkan tanggal
				$currentDate = now();
				$activeBudget = $this->getActiveBudgetForDate(
					$category,
					$category->user_id,
					$currentDate
				);

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
					"budget_amount" => $activeBudget ? $activeBudget->amount : 0,
					"formatted_budget_amount" => $activeBudget
						? Helper::formatMoney($activeBudget->amount)
						: Helper::formatMoney(0),
				];
			}
		);
	}

	/**
	 * Get all categories usage statistics (optimized with single query)
	 */
	public function getAllCategoriesUsage(
		User $user,
		?string $startDate = null,
		?string $endDate = null
	): Collection {
		$cacheKey = Helper::generateCacheKey("all_categories_usage", [
			"user_id" => $user->id,
			"start_date" => $startDate,
			"end_date" => $endDate,
		]);

		return Cache::remember(
			$cacheKey,
			config("wallet.cache_ttl"),
			function () use ($user, $startDate, $endDate) {
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

				return $query->get()->map(function ($category) use ($user) {
					$total = $category->transactions_sum_amount ?? 0;

					// Get active budget for current date
					$currentDate = now();
					$activeBudget = $this->getActiveBudgetForDate(
						$category,
						$user->id,
						$currentDate
					);

					return [
						"category" => $category,
						"total_amount" => $total,
						"transaction_count" => $category->transactions_count,
						"budget_usage" =>
							$activeBudget && $activeBudget->amount > 0
								? ($total / $activeBudget->amount) * 100
								: null,
						"budget_exceeded" =>
							$activeBudget && $total > $activeBudget->amount,
						"budget_amount" => $activeBudget ? $activeBudget->amount : 0,
						"formatted_budget_amount" => $activeBudget
							? Helper::formatMoney($activeBudget->amount)
							: Helper::formatMoney(0),
					];
				});
			}
		);
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
		$cacheKey = Helper::generateCacheKey("category_stats", [
			"user_id" => $user->id,
		]);

		return Cache::remember(
			$cacheKey,
			config("wallet.cache_ttl"),
			function () use ($user) {
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
				$currentDate = now();
				$startOfMonth = $currentDate->copy()->startOfMonth();
				$endOfMonth = $currentDate->copy()->endOfMonth();

				$categoriesWithBudget = Category::where("user_id", $user->id)
					->expense()
					->active()
					->whereHas("budgets", function ($query) use (
						$startOfMonth,
						$endOfMonth
					) {
						$query
							->where("is_active", true)
							->where("start_date", "<=", $endOfMonth)
							->where("end_date", ">=", $startOfMonth);
					})
					->count();

				// Hitung budget exceeded bulan ini
				$budgetExceededCount = 0;
				if ($categoriesWithBudget > 0) {
					$budgetExceededCount = Category::where("user_id", $user->id)
						->expense()
						->active()
						->whereHas("budgets", function ($query) use (
							$startOfMonth,
							$endOfMonth
						) {
							$query
								->where("is_active", true)
								->where("start_date", "<=", $endOfMonth)
								->where("end_date", ">=", $startOfMonth)
								->whereColumn("spent", ">", "amount");
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
			}
		);
	}

	/**
	 * Get categories with budget warnings (optimized)
	 */
	public function getBudgetWarnings(User $user, int $threshold = 80): Collection
	{
		$currentDate = now();
		$startOfMonth = $currentDate->copy()->startOfMonth();
		$endOfMonth = $currentDate->copy()->endOfMonth();

		$cacheKey = Helper::generateCacheKey("budget_warnings", [
			"user_id" => $user->id,
			"current_date" => $currentDate->format("Y-m-d"),
			"threshold" => $threshold,
		]);

		return Cache::remember(
			$cacheKey,
			config("wallet.cache_ttl") / 2,
			function () use (
				$user,
				$currentDate,
				$startOfMonth,
				$endOfMonth,
				$threshold
			) {
				// Single query dengan subquery untuk monthly total dan budget
				$categories = Category::where("user_id", $user->id)
					->expense()
					->active()
					->whereHas("budgets", function ($query) use (
						$startOfMonth,
						$endOfMonth
					) {
						$query
							->where("is_active", true)
							->where("start_date", "<=", $endOfMonth)
							->where("end_date", ">=", $startOfMonth);
					})
					->with([
						"budgets" => function ($query) use ($startOfMonth, $endOfMonth) {
							$query
								->where("is_active", true)
								->where("start_date", "<=", $endOfMonth)
								->where("end_date", ">=", $startOfMonth);
						},
					])
					->get();

				// Tambahkan monthly total dan filter berdasarkan threshold
				return $categories
					->map(function ($category) use ($currentDate) {
						// Dapatkan budget aktif untuk tanggal sekarang
						$budget = $this->getActiveBudgetForDate(
							$category,
							$category->user_id,
							$currentDate
						);

						if (!$budget) {
							return null;
						}

						// Hitung total expense untuk periode budget
						$total = $category
							->transactions()
							->where("type", TransactionType::EXPENSE)
							->whereBetween("transaction_date", [
								$budget->start_date,
								$budget->end_date,
							])
							->sum("amount");

						$usage =
							$budget && $budget->amount > 0
								? ($total / $budget->amount) * 100
								: 0;

						return [
							"category" => $category,
							"budget" => $budget,
							"usage_percentage" => $usage,
							"total_spent" => $total,
							"formatted_spent" => Helper::formatMoney($total),
							"formatted_budget_amount" => Helper::formatMoney($budget->amount),
							"is_exceeded" => $total > $budget->amount,
						];
					})
					->filter(
						fn($item) => $item && $item["usage_percentage"] >= $threshold
					)
					->values();
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

		// Buat tanggal untuk bulan tersebut
		$startDate = Carbon::create($year, $month, 1)->startOfMonth();
		$endDate = Carbon::create($year, $month, 1)->endOfMonth();

		$cacheKey = Helper::generateCacheKey("unbudgeted_categories", [
			"user_id" => $user->id,
			"month" => $month,
			"year" => $year,
		]);

		return Cache::remember(
			$cacheKey,
			config("wallet.cache_ttl"),
			function () use ($user, $startDate, $endDate) {
				return Category::expense()
					->forUser($user->id)
					->active()
					->whereDoesntHave("budgets", function ($query) use (
						$startDate,
						$endDate
					) {
						$query
							->where("is_active", true)
							->where("start_date", "<=", $endDate)
							->where("end_date", ">=", $startDate);
					})
					->get();
			}
		);
	}

	/**
	 * Get popular categories (most used)
	 */
	public function getPopularCategories(
		User $user,
		int $limit = 5,
		string $type = null
	): Collection {
		$cacheKey = Helper::generateCacheKey("popular_categories", [
			"user_id" => $user->id,
			"limit" => $limit,
			"type" => $type,
		]);

		return Cache::remember(
			$cacheKey,
			config("wallet.cache_ttl"),
			function () use ($user, $limit, $type) {
				$query = Category::where("user_id", $user->id)
					->withCount("transactions")
					->orderBy("transactions_count", "desc")
					->limit($limit);

				if ($type) {
					$query->where("type", $type);
				}

				return $query->get();
			}
		);
	}

	/**
	 * Get active budget untuk periode tertentu
	 */
	private function getActiveBudgetForPeriod(
		Category $category,
		int $userId,
		string $periodType,
		int $periodValue,
		int $year,
		?Carbon $startDate = null,
		?Carbon $endDate = null
	): ?Budget {
		$query = $category
			->budgets()
			->where("user_id", $userId)
			->where("period_type", $periodType)
			->where("period_value", $periodValue)
			->where("year", $year)
			->where("is_active", true);

		if ($startDate && $endDate) {
			$query
				->where("start_date", "<=", $endDate)
				->where("end_date", ">=", $startDate);
		}

		return $query->first();
	}

	/**
	 * Get active budget untuk tanggal tertentu
	 */
	private function getActiveBudgetForDate(
		Category $category,
		int $userId,
		Carbon $date
	): ?Budget {
		return $category
			->budgets()
			->where("user_id", $userId)
			->where("is_active", true)
			->where("start_date", "<=", $date)
			->where("end_date", ">=", $date)
			->first();
	}

	/**
	 * Invalidate user category cache
	 */
	private function invalidateUserCategoryCache(int $userId): void
	{
		$patterns = [
			Helper::generateCacheKey("user_categories", ["user_id" => $userId, "*"]),
			Helper::generateCacheKey("user_categories_by_type", [
				"user_id" => $userId,
				"*",
			]),
			Helper::generateCacheKey("categories_dropdown", [
				"user_id" => $userId,
				"*",
			]),
			Helper::generateCacheKey("category_stats", ["user_id" => $userId]),
			Helper::generateCacheKey("categories_monthly_totals", [
				"user_id" => $userId,
				"*",
			]),
			Helper::generateCacheKey("category_usage", ["user_id" => $userId, "*"]),
			Helper::generateCacheKey("all_categories_usage", [
				"user_id" => $userId,
				"*",
			]),
			Helper::generateCacheKey("budget_warnings", ["user_id" => $userId, "*"]),
			Helper::generateCacheKey("unbudgeted_categories", [
				"user_id" => $userId,
				"*",
			]),
			Helper::generateCacheKey("popular_categories", [
				"user_id" => $userId,
				"*",
			]),
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
		$cacheKey = Helper::generateCacheKey("user_categories_with_stats", [
			"user_id" => $user->id,
			"type" => $type,
			"include_inactive" => $includeInactive,
		]);

		return Cache::remember(
			$cacheKey,
			config("wallet.cache_ttl"),
			function () use ($user, $type, $includeInactive) {
				$categories = $this->getUserCategories($type, $includeInactive);
				$stats = $this->getCategoryStats($user);

				return [
					"categories" => $categories,
					"stats" => $stats,
				];
			}
		);
	}

	/**
	 * Get categories with current budget information
	 */
	public function getCategoriesWithCurrentBudget(User $user): Collection
	{
		$currentDate = now();

		return Category::where("user_id", $user->id)
			->expense()
			->active()
			->with([
				"budgets" => function ($query) use ($currentDate) {
					$query
						->where("is_active", true)
						->where("start_date", "<=", $currentDate)
						->where("end_date", ">=", $currentDate);
				},
			])
			->get()
			->map(function ($category) use ($currentDate) {
				$budget = $category->budgets->first();
				$category->current_budget = $budget;

				if ($budget) {
					// Hitung total pengeluaran dalam periode budget
					$spent = $category
						->transactions()
						->where("type", TransactionType::EXPENSE)
						->whereBetween("transaction_date", [
							$budget->start_date,
							$budget->end_date,
						])
						->sum("amount");

					$category->current_spent = $spent;
					$category->budget_remaining = max(0, $budget->amount - $spent);
					$category->budget_usage_percentage =
						$budget->amount > 0
							? min(100, ($spent / $budget->amount) * 100)
							: 0;
				}

				return $category;
			});
	}

	/**
	 * Get category budget summary for a specific period
	 */
	public function getCategoryBudgetSummary(
		User $user,
		string $periodType,
		int $periodValue,
		int $year
	): Collection {
		return Category::where("user_id", $user->id)
			->expense()
			->active()
			->with([
				"budgets" => function ($query) use ($periodType, $periodValue, $year) {
					$query
						->where("period_type", $periodType)
						->where("period_value", $periodValue)
						->where("year", $year)
						->where("is_active", true);
				},
			])
			->get()
			->filter(fn($category) => $category->budgets->isNotEmpty())
			->map(function ($category) use ($periodType, $periodValue, $year) {
				$budget = $category->budgets->first();

				// Hitung total pengeluaran untuk periode ini
				$spent = $category
					->transactions()
					->where("type", TransactionType::EXPENSE)
					->when($budget, function ($query) use ($budget) {
						return $query->whereBetween("transaction_date", [
							$budget->start_date,
							$budget->end_date,
						]);
					})
					->sum("amount");

				return [
					"category" => $category,
					"budget" => $budget,
					"spent" => $spent,
					"remaining" => $budget ? max(0, $budget->amount - $spent) : 0,
					"usage_percentage" =>
						$budget && $budget->amount > 0
							? min(100, ($spent / $budget->amount) * 100)
							: 0,
				];
			});
	}
}
