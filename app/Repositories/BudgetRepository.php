<?php

namespace Modules\Wallet\Repositories;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Modules\Wallet\Models\Budget;
use Modules\Wallet\Models\Category;
use Modules\Wallet\Models\Account;
use Modules\Wallet\Helpers\Helper;
use Modules\Wallet\Enums\PeriodType;
use Modules\Wallet\Enums\CategoryType;

class BudgetRepository extends BaseRepository
{
	public function __construct(Budget $model)
	{
		parent::__construct($model);
	}

	/**
	 * Get optimized dashboard data for budgets
	 */
	public function getDashboardData(User $user, Carbon $now): array
	{
		$cacheKey = Helper::generateCacheKey("dashboard_budgets", [
			"user_id" => $user->id,
			"current_date" => $now->toDateString(),
		]);

		return Cache::remember($cacheKey, 300, function () use ($user, $now) {
			return DB::transaction(function () use ($user, $now) {
				// Current active budgets
				$summary = DB::table("budgets as b")
					->join("categories as c", "b.category_id", "=", "c.id")
					->where("b.user_id", $user->id)
					->where("b.is_active", true)
					->where("b.start_date", "<=", $now)
					->where("b.end_date", ">=", $now)
					->select(
						"b.id",
						"b.name",
						"b.amount",
						"b.spent",
						"b.start_date",
						"b.end_date",
						"c.name as category_name",
						"c.icon as category_icon",
						DB::raw("(b.spent / b.amount * 100) as usage_percentage")
					)
					->get()
					->toArray();

				// Budget stats
				$stats = $this->calculateBudgetStats($summary);

				// Budget warnings
				$warnings = $this->getBudgetWarnings($summary);

				return [
					"summary" => $summary,
					"stats" => $stats,
					"warnings" => $warnings,
				];
			});
		});
	}

	/**
	 * Calculate budget stats
	 */
	protected function calculateBudgetStats(array $budgets): array
	{
		if (empty($budgets)) {
			return [
				"total" => 0,
				"over_budget" => 0,
				"total_amount" => 0,
				"total_spent" => 0,
			];
		}

		$overBudget = array_filter($budgets, fn($b) => $b->usage_percentage > 100);

		return [
			"total" => count($budgets),
			"over_budget" => count($overBudget),
			"total_amount" => array_sum(array_column($budgets, "amount")),
			"total_spent" => array_sum(array_column($budgets, "spent")),
		];
	}

	/**
	 * Get budget warnings
	 */
	protected function getBudgetWarnings(array $budgets): array
	{
		$threshold = 80;

		return collect($budgets)
			->filter(fn($budget) => $budget->usage_percentage >= $threshold)
			->map(function ($budget) {
				return [
					"budget_id" => $budget->id,
					"budget_name" => $budget->name,
					"category_name" => $budget->category_name,
					"usage_percentage" => $budget->usage_percentage,
					"spent" => $budget->spent,
					"amount" => $budget->amount,
					"message" => "{$budget->name} using {$budget->usage_percentage}% of total {$budget->amount}",
				];
			})
			->values()
			->toArray();
	}

	/**
	 * Get all budgets for current user with filters (cached)
	 */
	public function getUserBudgets(
		array $filters = [],
		bool $includeInactive = false
	): Collection {
		$user = auth()->user();
		$cacheKey = Helper::generateCacheKey("user_budgets", [
			"user_id" => $user->id,
			"filters" => $filters,
			"include_inactive" => $includeInactive,
		]);

		return Cache::remember(
			$cacheKey,
			config("wallet.cache_ttl"),
			function () use ($user, $filters, $includeInactive) {
				$query = Budget::with(["category", "accounts"])->where(
					"user_id",
					$user->id
				);

				// Apply filters
				if (isset($filters["category_id"])) {
					$query->where("category_id", $filters["category_id"]);
				}

				if (isset($filters["period_type"])) {
					$query->where("period_type", $filters["period_type"]);
				}

				if (isset($filters["year"])) {
					$query->where("year", $filters["year"]);
				}

				if (isset($filters["period_value"])) {
					$query->where("period_value", $filters["period_value"]);
				}

				if (isset($filters["is_active"])) {
					$query->where("is_active", $filters["is_active"]);
				}

				if (!$includeInactive) {
					$query->where("is_active", true);
				}

				// Date range filters
				if (isset($filters["start_date"])) {
					$query->whereDate("start_date", ">=", $filters["start_date"]);
				}

				if (isset($filters["end_date"])) {
					$query->whereDate("end_date", "<=", $filters["end_date"]);
				}

				// Search by name
				if (isset($filters["search"])) {
					$query->where(function ($q) use ($filters) {
						$q->where("name", "LIKE", "%{$filters["search"]}%")->orWhereHas(
							"category",
							function ($q) use ($filters) {
								$q->where("name", "LIKE", "%{$filters["search"]}%");
							}
						);
					});
				}

				return $query->orderBy("start_date", "desc")->get();
			}
		);
	}

	/**
	 * Get paginated budgets
	 */
	public function getPaginatedBudgets(int $perPage = 15, array $filters = [])
	{
		$user = auth()->user();

		$query = Budget::with(["category", "accounts"])->where(
			"user_id",
			$user->id
		);

		// Apply filters
		if (isset($filters["category_id"])) {
			$query->where("category_id", $filters["category_id"]);
		}

		if (isset($filters["period_type"])) {
			$query->where("period_type", $filters["period_type"]);
		}

		if (isset($filters["year"])) {
			$query->where("year", $filters["year"]);
		}

		if (isset($filters["is_active"])) {
			$query->where("is_active", $filters["is_active"]);
		}

		if (isset($filters["search"])) {
			$query->where(function ($q) use ($filters) {
				$q->where("name", "LIKE", "%{$filters["search"]}%")->orWhereHas(
					"category",
					function ($q) use ($filters) {
						$q->where("name", "LIKE", "%{$filters["search"]}%");
					}
				);
			});
		}

		return $query->orderBy("start_date", "desc")->paginate($perPage);
	}

	/**
	 * Create new budget
	 */
	public function createBudget(array $data, User $user): Budget
	{
		// Set user_id
		$data["user_id"] = $user->id;

		// Calculate dates if not provided
		if (!isset($data["start_date"]) || !isset($data["end_date"])) {
			$dates = $this->calculatePeriodDates(
				$data["period_type"] ?? PeriodType::MONTHLY,
				$data["period_value"] ?? date("m"),
				$data["year"] ?? date("Y")
			);
			$data["start_date"] = $dates["start_date"];
			$data["end_date"] = $dates["end_date"];
		}

		DB::beginTransaction();

		try {
			// Handle accounts
			$accounts = $data["accounts"] ?? [];
			unset($data["accounts"]);

			// Create budget
			$budget = Budget::create($data);

			// Attach accounts
			if (!empty($accounts)) {
				$budget->accounts()->sync($accounts);
			}

			DB::commit();

			// Invalidate cache
			Cache::flush();

			return $budget->load(["category", "accounts"]);
		} catch (\Exception $e) {
			DB::rollBack();
			throw $e;
		}
	}

	/**
	 * Update budget
	 */
	public function updateBudget(Budget $budget, array $data): Budget
	{
		DB::beginTransaction();

		try {
			// Handle accounts
			if (isset($data["accounts"])) {
				$budget->accounts()->sync($data["accounts"]);
				unset($data["accounts"]);
			}

			// Update budget
			$budget->update($data);

			DB::commit();

			// Invalidate cache
			Cache::flush();

			return $budget->fresh()->load(["category", "accounts"]);
		} catch (\Exception $e) {
			DB::rollBack();
			throw $e;
		}
	}

	/**
	 * Delete budget
	 */
	public function deleteBudget(Budget $budget): bool
	{
		// Invalidate cache
		Cache::flush();

		return $budget->delete();
	}

	/**
	 * Calculate period dates
	 */
	public function calculatePeriodDates(
		string $periodType,
		int $periodValue,
		int $year
	): array {
		switch ($periodType) {
			case PeriodType::MONTHLY:
				$startDate = Carbon::create($year, $periodValue, 1)->startOfMonth();
				$endDate = $startDate->copy()->endOfMonth();
				break;

			case PeriodType::WEEKLY:
				$startDate = Carbon::create($year, 1, 1)->startOfWeek();
				for ($i = 1; $i < $periodValue; $i++) {
					$startDate->addWeek();
				}
				$endDate = $startDate->copy()->endOfWeek();
				break;

			case PeriodType::BIWEEKLY:
				$startDate = Carbon::create($year, 1, 1)->startOfWeek();
				for ($i = 1; $i < $periodValue; $i++) {
					$startDate->addWeeks(2);
				}
				$endDate = $startDate
					->copy()
					->addWeeks(2)
					->subDay()
					->endOfDay();
				break;

			case PeriodType::QUARTERLY:
				$quarterMonth = ($periodValue - 1) * 3 + 1;
				$startDate = Carbon::create($year, $quarterMonth, 1)->startOfMonth();
				$endDate = $startDate
					->copy()
					->addMonths(2)
					->endOfMonth();
				break;

			case PeriodType::YEARLY:
				$startDate = Carbon::create($year, 1, 1)->startOfYear();
				$endDate = $startDate->copy()->endOfYear();
				break;

			case PeriodType::CUSTOM:
				// For custom, dates should be provided
				$startDate = now()->startOfMonth();
				$endDate = now()->endOfMonth();
				break;

			default:
				$startDate = now()->startOfMonth();
				$endDate = now()->endOfMonth();
		}

		return [
			"start_date" => $startDate,
			"end_date" => $endDate,
		];
	}

	/**
	 * Get current active budgets
	 */
	public function getCurrentBudgets(User $user): Collection
	{
		$cacheKey = Helper::generateCacheKey("current_budgets", [
			"user_id" => $user->id,
		]);

		return Cache::remember(
			$cacheKey,
			config("wallet.cache_ttl") / 2, // Shorter cache for current data
			function () use ($user) {
				return Budget::with(["category", "accounts"])
					->where("user_id", $user->id)
					->where("is_active", true)
					->where("start_date", "<=", now())
					->where("end_date", ">=", now())
					->orderBy("period_type")
					->orderBy("start_date")
					->get();
			}
		);
	}

	/**
	 * Get budgets by category
	 */
	public function getBudgetsByCategory(Category $category): Collection
	{
		$cacheKey = Helper::generateCacheKey("budgets_by_category", [
			"category_id" => $category->id,
		]);

		return Cache::remember(
			$cacheKey,
			config("wallet.cache_ttl"),
			function () use ($category) {
				return Budget::with(["accounts"])
					->where("category_id", $category->id)
					->orderBy("start_date", "desc")
					->get();
			}
		);
	}

	/**
	 * Get budget statistics
	 */
	public function getBudgetStats(User $user): array
	{
		$cacheKey = Helper::generateCacheKey("budget_stats", [
			"user_id" => $user->id,
		]);

		return Cache::remember(
			$cacheKey,
			config("wallet.cache_ttl"),
			function () use ($user) {
				$totalBudgets = Budget::where("user_id", $user->id)->count();
				$activeBudgets = Budget::where("user_id", $user->id)
					->where("is_active", true)
					->count();

				// Current period budgets
				$currentBudgets = Budget::where("user_id", $user->id)
					->where("is_active", true)
					->where("start_date", "<=", now())
					->where("end_date", ">=", now())
					->count();

				// Over budget count
				$overBudgetCount = Budget::where("user_id", $user->id)
					->where("is_active", true)
					->whereColumn("spent", ">", "amount")
					->where("start_date", "<=", now())
					->where("end_date", ">=", now())
					->count();

				// Total budget amount
				$totalBudgetAmount = Budget::where("user_id", $user->id)
					->where("is_active", true)
					->where("start_date", "<=", now())
					->where("end_date", ">=", now())
					->sum("amount");

				// Total spent
				$totalSpent = Budget::where("user_id", $user->id)
					->where("is_active", true)
					->where("start_date", "<=", now())
					->where("end_date", ">=", now())
					->sum("spent");

				return [
					"total" => $totalBudgets,
					"active" => $activeBudgets,
					"current" => $currentBudgets,
					"over_budget" => $overBudgetCount,
					"total_amount" => $totalBudgetAmount / 100,
					"total_spent" => $totalSpent / 100,
					"total_remaining" => max(0, $totalBudgetAmount - $totalSpent),
					"overall_usage" =>
						$totalBudgetAmount > 0
							? min(100, ($totalSpent / $totalBudgetAmount) * 100)
							: 0,
				];
			}
		);
	}

	/**
	 * Get budget summary for dashboard
	 */
	public function getDashboardSummary(User $user): array
	{
		$currentBudgets = $this->getCurrentBudgets($user);
		$stats = $this->getBudgetStats($user);

		// Categorize by usage
		$onTrack = $currentBudgets->filter(function ($budget) {
			return $budget->usage_percentage <= 70;
		});

		$warning = $currentBudgets->filter(function ($budget) {
			return $budget->usage_percentage > 70 && $budget->usage_percentage <= 90;
		});

		$danger = $currentBudgets->filter(function ($budget) {
			return $budget->usage_percentage > 90 || $budget->is_over_budget;
		});

		return [
			"current_budgets" => $currentBudgets,
			"stats" => $stats,
			"categorized" => [
				"on_track" => $onTrack,
				"warning" => $warning,
				"danger" => $danger,
			],
			"top_categories" => $currentBudgets->sortByDesc("spent")->take(5),
		];
	}

	/**
	 * Update all budgets spent amounts
	 */
	public function updateAllSpentAmounts(User $user): void
	{
		$budgets = Budget::where("user_id", $user->id)
			->where("is_active", true)
			->where("end_date", ">=", now()->subMonth()) // Only recent budgets
			->get();

		foreach ($budgets as $budget) {
			$budget->updateSpentAmount();
		}

		// Invalidate cache
		Cache::flush();
	}

	/**
	 * Get budgets for specific period
	 */
	public function getPeriodBudgets(
		User $user,
		string $periodType,
		int $periodValue,
		int $year
	): Collection {
		return Budget::with(["category", "accounts"])
			->where("user_id", $user->id)
			->where("period_type", $periodType)
			->where("period_value", $periodValue)
			->where("year", $year)
			->orderBy("start_date")
			->get();
	}

	/**
	 * Get budgets expiring soon
	 */
	public function getExpiringBudgets(User $user, int $days = 7): Collection
	{
		return Budget::with(["category"])
			->where("user_id", $user->id)
			->where("is_active", true)
			->where("end_date", ">=", now())
			->where("end_date", "<=", now()->addDays($days))
			->orderBy("end_date")
			->get();
	}

	/**
	 * Create next period budget from existing
	 */
	public function createNextPeriodBudget(Budget $budget): Budget
	{
		$nextPeriod = $this->calculateNextPeriod($budget);

		$data = $budget->toArray();
		$data["period_type"] = $budget->period_type;
		$data["period_value"] = $nextPeriod["period_value"];
		$data["year"] = $nextPeriod["year"];
		$data["start_date"] = $nextPeriod["start_date"];
		$data["end_date"] = $nextPeriod["end_date"];

		// Adjust amount based on rollover
		if ($budget->rollover_unused) {
			$rolloverAmount = $budget->getRolloverAmount();
			$data["amount"] += $rolloverAmount;
		}

		// Reset spent
		$data["spent"] = 0;

		// Remove ID and timestamps
		unset(
			$data["id"],
			$data["created_at"],
			$data["updated_at"],
			$data["deleted_at"]
		);

		return $this->createBudget($data, $budget->user);
	}

	public function getActiveBudgetForDate(
		Category $category,
		int $userId,
		?Carbon $date = null
	): ?Budget {
		$date = $date ?? now();
		return $this->model
			->where("category_id", $category->id)
			->where("user_id", $userId)
			->where("is_active", true)
			->whereDate("start_date", "<=", $date)
			->whereDate("end_date", ">=", $date)
			->first();
	}

	/**
	 * Calculate next period
	 */
	private function calculateNextPeriod(Budget $budget): array
	{
		switch ($budget->period_type) {
			case PeriodType::MONTHLY:
				$nextDate = $budget->start_date->copy()->addMonth();
				return [
					"period_value" => $nextDate->month,
					"year" => $nextDate->year,
					"start_date" => $nextDate->startOfMonth(),
					"end_date" => $nextDate->endOfMonth(),
				];

			case PeriodType::WEEKLY:
				$nextDate = $budget->start_date->copy()->addWeek();
				$weekNumber = $nextDate->weekOfYear;
				return [
					"period_value" => $weekNumber,
					"year" => $nextDate->year,
					"start_date" => $nextDate->startOfWeek(),
					"end_date" => $nextDate->endOfWeek(),
				];

			case PeriodType::BIWEEKLY:
				$nextDate = $budget->start_date->copy()->addWeeks(2);
				$biweekNumber = ceil($nextDate->weekOfYear / 2);
				return [
					"period_value" => $biweekNumber,
					"year" => $nextDate->year,
					"start_date" => $nextDate,
					"end_date" => $nextDate
						->copy()
						->addWeeks(2)
						->subDay(),
				];

			case PeriodType::QUARTERLY:
				$nextDate = $budget->start_date->copy()->addMonths(3);
				$quarter = ceil($nextDate->month / 3);
				return [
					"period_value" => $quarter,
					"year" => $nextDate->year,
					"start_date" => $nextDate->startOfQuarter(),
					"end_date" => $nextDate->endOfQuarter(),
				];

			case PeriodType::YEARLY:
				$nextDate = $budget->start_date->copy()->addYear();
				return [
					"period_value" => 1,
					"year" => $nextDate->year,
					"start_date" => $nextDate->startOfYear(),
					"end_date" => $nextDate->endOfYear(),
				];

			default:
				$nextDate = $budget->start_date->copy()->addMonth();
				return [
					"period_value" => $nextDate->month,
					"year" => $nextDate->year,
					"start_date" => $nextDate->startOfMonth(),
					"end_date" => $nextDate->endOfMonth(),
				];
		}
	}

	public function updateSpentAmount(
		int $budgetId,
		int $amount,
		string $operation = "add"
	): bool {
		$budget = $this->find($budgetId);

		if (!$budget) {
			return false;
		}

		switch ($operation) {
			case "add":
				$budget->spent = $budget->spent->plus($amount);
				break;
			case "subtract":
				$budget->spent = max(0, $budget->spent->getAmount()->toInt() - $amount);
				break;
			case "set":
				$budget->spent = $amount;
				break;
		}

		$success = $budget->save();
		if ($success) {
			Cache::flush();
		}

		return $success;
	}
}
