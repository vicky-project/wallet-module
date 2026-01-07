<?php

namespace Modules\Wallet\Repositories;

use App\Models\User;
use Carbon\Carbon;
use Brick\Money\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Wallet\Enums\CategoryType;
use Modules\Wallet\Enums\TransactionType;
use Modules\Wallet\Models\Budget;
use Modules\Wallet\Models\Category;
use Modules\Wallet\Models\Transaction;

class BudgetRepository extends BaseRepository
{
	private const CACHE_DURATION = 3600; // 1 jam

	public function __construct(Budget $model)
	{
		$this->model = $model;
	}

	/**
	 * Create budget - HANYA UNTUK KATEGORI EXPENSE
	 */
	public function createBudget(array $data, User $user): Budget
	{
		$category = Category::find($data["category_id"]);
		if (!$category || $category->type !== CategoryType::EXPENSE) {
			throw new \Exception(
				"Anggaran hanya dapat dibuat untuk kategori pengeluaran"
			);
		}

		$data["user_id"] = $user->id;
		$data["amount"] = (int) ($data["amount"] ?? 0);
		$data["month"] = $data["month"] ?? Carbon::now()->month;
		$data["year"] = $data["year"] ?? Carbon::now()->year;
		$data["spent"] = 0;
		$data["is_active"] = true;

		// Invalidate cache
		$this->invalidateUserBudgetCache($user->id, $data["month"], $data["year"]);

		return $this->create($data);
	}

	/**
	 * Update budget
	 */
	public function updateBudget(int $id, array $data): Budget
	{
		if (isset($data["amount"])) {
			$data["amount"] = (int) ($data["amount"] * 100);
		}

		$budget = $this->find($id);
		if ($budget) {
			$this->invalidateUserBudgetCache(
				$budget->user_id,
				$budget->month,
				$budget->year
			);
		}

		$this->update($id, $data);
		return $this->find($id);
	}

	/**
	 * Get user budgets with caching
	 */
	public function getUserBudgets(User $user, array $filters = []): Collection
	{
		$month = $filters["month"] ?? Carbon::now()->month;
		$year = $filters["year"] ?? Carbon::now()->year;

		$cacheKey = $this->getUserBudgetsCacheKey($user->id, $month, $year);

		return Cache::remember($cacheKey, self::CACHE_DURATION, function () use (
			$user,
			$filters
		) {
			$query = $this->model->with(["category"])->where("user_id", $user->id);

			if (isset($filters["month"])) {
				$query->where("month", $filters["month"]);
			}
			if (isset($filters["year"])) {
				$query->where("year", $filters["year"]);
			}
			if (isset($filters["category_id"])) {
				$query->where("category_id", $filters["category_id"]);
			}
			if (
				!isset($filters["include_inactive"]) ||
				$filters["include_inactive"]
			) {
				$query->where("is_active", true);
			}

			$budgets = $query
				->orderBy("year", "desc")
				->orderBy("month", "desc")
				->get();

			// Load spent amounts in single query
			if ($budgets->isNotEmpty()) {
				$this->loadSpentAmounts($budgets);
			}

			return $budgets;
		});
	}

	/**
	 * Load spent amounts for multiple budgets efficiently
	 */
	private function loadSpentAmounts(Collection $budgets): void
	{
		$budgetIds = $budgets->pluck("id")->toArray();
		$userIds = $budgets
			->pluck("user_id")
			->unique()
			->toArray();

		if (empty($budgetIds)) {
			return;
		}

		// Get all spent amounts in single query
		$spentAmounts = Transaction::select([
			"category_id",
			DB::raw("MONTH(transaction_date) as month"),
			DB::raw("YEAR(transaction_date) as year"),
			DB::raw("SUM(amount) as total_spent"),
		])
			->whereIn("user_id", $userIds)
			->whereIn("category_id", $budgets->pluck("category_id")->unique())
			->where("type", TransactionType::EXPENSE)
			->whereBetween("transaction_date", [
				$budgets->min("year") . "-" . $budgets->min("month") . "-01",
				$budgets->max("year") . "-" . $budgets->max("month") . "-31",
			])
			->groupBy(
				"category_id",
				DB::raw("MONTH(transaction_date)"),
				DB::raw("YEAR(transaction_date)")
			)
			->get()
			->keyBy(function ($item) {
				return $item->category_id . "-" . $item->month . "-" . $item->year;
			});

		// Map spent amounts to budgets
		foreach ($budgets as $budget) {
			$key = $budget->category_id . "-" . $budget->month . "-" . $budget->year;

			$budget->spent = Money::ofMinor(
				$spentAmounts[$key]->total_spent ?? 0,
				"IDR"
			)
				->getAmount()
				->toInt();
			$budget->percentage =
				$budget->amount->getAmount()->toInt() > 0
					? round(
						($budget->spent->getAmount()->toInt() /
							$budget->amount->getAmount()->toInt()) *
							100,
						2
					)
					: 0;
		}
	}

	/**
	 * Get current month's budget
	 */
	public function getCurrentBudget(User $user): Collection
	{
		$currentMonth = Carbon::now()->month;
		$currentYear = Carbon::now()->year;

		return $this->getUserBudgets($user, [
			"month" => $currentMonth,
			"year" => $currentYear,
			"include_inactive" => false,
		]);
	}

	/**
	 * Get budget summary with optimized queries
	 */
	public function getBudgetSummary(
		User $user,
		int $month = null,
		int $year = null
	): array {
		$month = $month ?? Carbon::now()->month;
		$year = $year ?? Carbon::now()->year;

		$cacheKey = $this->getBudgetSummaryCacheKey($user->id, $month, $year);

		return Cache::remember($cacheKey, self::CACHE_DURATION, function () use (
			$user,
			$month,
			$year
		) {
			// Get budgets with spent amounts
			$budgets = $this->getUserBudgets($user, [
				"month" => $month,
				"year" => $year,
				"include_inactive" => false,
			]);
			dd($budgets);

			// Calculate totals
			$totalBudget = $budgets->sum(
				fn(Budget $budget) => $budget->amount->getAmount()->toInt()
			);
			$totalSpent = $budgets->sum(
				fn(Budget $budget) => $budget->spent->getAmount()->toInt()
			);
			dd($totalSpent);

			$totalRemaining = max(0, $totalBudget - $totalSpent);
			$budgetUsagePercentage =
				$totalBudget > 0 ? round(($totalSpent / $totalBudget) * 100, 2) : 0;

			// Get unbudgeted expenses in single query
			$budgetedCategoryIds = $budgets->pluck("category_id")->toArray();

			$unbudgetedData = $this->getUnbudgetedExpenses(
				$user->id,
				$month,
				$year,
				$budgetedCategoryIds
			);

			// Get total expenses
			$totalExpenses = Transaction::where("user_id", $user->id)
				->where("type", TransactionType::EXPENSE)
				->whereMonth("transaction_date", $month)
				->whereYear("transaction_date", $year)
				->sum("amount");

			$budgetedExpensePercentage =
				$totalExpenses > 0 ? round(($totalSpent / $totalExpenses) * 100, 2) : 0;

			return [
				"budgets" => $budgets,
				"total_budget" => $totalBudget,
				"total_spent" => $totalSpent,
				"total_remaining" => $totalRemaining,
				"budget_usage_percentage" => $budgetUsagePercentage,
				"formatted_total_budget" => $this->formatMoney($totalBudget),
				"formatted_total_spent" => $this->formatMoney($totalSpent),
				"formatted_total_remaining" => $this->formatMoney($totalRemaining),
				"unbudgeted_categories" => $unbudgetedData["categories"],
				"unbudgeted_expenses" => $unbudgetedData["total"],
				"formatted_unbudgeted_expenses" => $this->formatMoney(
					$unbudgetedData["total"]
				),
				"total_expenses" => $totalExpenses,
				"formatted_total_expenses" => $this->formatMoney($totalExpenses),
				"budgeted_expense_percentage" => $budgetedExpensePercentage,
			];
		});
	}

	/**
	 * Get unbudgeted expenses efficiently
	 */
	private function getUnbudgetedExpenses(
		int $userId,
		int $month,
		int $year,
		array $budgetedCategoryIds
	): array {
		// Gunakan query terpisah untuk menghindari masalah GROUP BY
		// 1. Dapatkan kategori tanpa budget
		$categories = Category::expense()
			->forUser($userId)
			->whereDoesntHave("budgets", function ($query) use ($month, $year) {
				$query
					->where("month", $month)
					->where("year", $year)
					->where("is_active", true);
			})
			->get();

		if ($categories->isEmpty()) {
			return [
				"categories" => $categories,
				"total" => 0,
			];
		}

		// 2. Hitung total expenses untuk kategori-kategori tersebut dalam 1 query
		$categoryExpenses = Transaction::where("user_id", $userId)
			->where("type", TransactionType::EXPENSE)
			->whereIn("category_id", $categories->pluck("id"))
			->whereMonth("transaction_date", $month)
			->whereYear("transaction_date", $year)
			->select(["category_id", DB::raw("SUM(amount) as total_spent")])
			->groupBy("category_id")
			->pluck("total_spent", "category_id");

		// 3. Gabungkan data
		$totalUnbudgeted = 0;
		foreach ($categories as $category) {
			$expense = $categoryExpenses[$category->id] ?? 0;
			$category->category_expense = $expense;
			$totalUnbudgeted += $expense;
		}

		return [
			"categories" => $categories,
			"total" => $totalUnbudgeted,
		];
	}

	/**
	 * Update all budgets spent amounts for a period
	 */
	public function updateAllBudgetsSpent(
		User $user,
		$month = null,
		$year = null
	): void {
		$month = $month ?? Carbon::now()->month;
		$year = $year ?? Carbon::now()->year;

		// Invalidate cache instead of loading all budgets
		$this->invalidateUserBudgetCache($user->id, $month, $year);
		$this->invalidateBudgetSummaryCache($user->id, $month, $year);
	}

	/**
	 * Update spent amounts when a transaction is created/updated
	 */
	public function updateSpentOnTransaction(Transaction $transaction): void
	{
		if ($transaction->type !== TransactionType::EXPENSE) {
			return;
		}

		$month = $transaction->transaction_date->month;
		$year = $transaction->transaction_date->year;

		// Invalidate caches for the affected period
		$this->invalidateUserBudgetCache($transaction->user_id, $month, $year);
		$this->invalidateBudgetSummaryCache($transaction->user_id, $month, $year);
	}

	/**
	 * Get budget suggestions based on previous spending
	 */
	public function getBudgetSuggestions(User $user, int $month, int $year): array
	{
		$previousMonth = $month - 1;
		$previousYear = $year;

		if ($previousMonth === 0) {
			$previousMonth = 12;
			$previousYear = $year - 1;
		}

		// Get previous expenses with categories in single query
		$previousExpenses = Transaction::with("category")
			->where("user_id", $user->id)
			->where("type", TransactionType::EXPENSE)
			->whereMonth("transaction_date", $previousMonth)
			->whereYear("transaction_date", $previousYear)
			->selectRaw("category_id, SUM(amount) as total_spent")
			->groupBy("category_id")
			->get()
			->filter(
				fn($expense) => $expense->category &&
					$expense->category->type === CategoryType::EXPENSE
			);

		return $previousExpenses
			->map(function ($expense) {
				$suggestedAmount = round($expense->total_spent / 100) * 1.1; // Convert to IDR + 10% buffer

				return [
					"category_id" => $expense->category_id,
					"category_name" => $expense->category->name,
					"previous_spent" => $expense->total_spent / 100,
					"suggested_amount" => $suggestedAmount,
					"formatted_suggested_amount" => $this->formatMoney(
						$suggestedAmount * 100
					),
				];
			})
			->toArray();
	}

	/**
	 * Get budget health status with optimized queries
	 */
	public function getBudgetHealthStatus(
		User $user,
		int $month = null,
		int $year = null
	): array {
		$month = $month ?? Carbon::now()->month;
		$year = $year ?? Carbon::now()->year;

		$cacheKey = $this->getHealthStatusCacheKey($user->id, $month, $year);

		return Cache::remember($cacheKey, self::CACHE_DURATION, function () use (
			$user,
			$month,
			$year
		) {
			// Get budgets with spent already calculated
			$budgets = $this->getUserBudgets($user, [
				"month" => $month,
				"year" => $year,
				"include_inactive" => false,
			]);

			// Count budgets by status
			$exceeded = $budgets
				->filter(fn($b) => ($b->percentage ?? 0) >= 100)
				->count();
			$warning = $budgets
				->filter(
					fn($b) => ($b->percentage ?? 0) >= 80 && ($b->percentage ?? 0) < 100
				)
				->count();
			$moderate = $budgets
				->filter(
					fn($b) => ($b->percentage ?? 0) >= 50 && ($b->percentage ?? 0) < 80
				)
				->count();
			$good = $budgets->filter(fn($b) => ($b->percentage ?? 0) < 50)->count();

			// Get expense categories count
			$totalExpenseCategories = Category::where("type", "expense")
				->where("is_budgetable", true)
				->where(function ($query) use ($user) {
					$query->where("user_id", $user->id)->orWhereNull("user_id");
				})
				->count();

			$budgetedCategories = $budgets->count();
			$unbudgetedCategories = $totalExpenseCategories - $budgetedCategories;

			// Calculate health score
			$healthScore =
				$totalExpenseCategories > 0
					? max(
						0,
						min(
							100,
							round(
								(($good * 1 +
									$moderate * 0.7 +
									$warning * 0.4 -
									$exceeded * 0.5) /
									$totalExpenseCategories) *
									100,
								0
							)
						)
					)
					: 0;

			// Determine health status
			if ($healthScore >= 80) {
				$healthStatus = "excellent";
				$healthStatusColor = "success";
				$healthMessage = "Anggaran Anda dalam kondisi sangat baik!";
			} elseif ($healthScore >= 60) {
				$healthStatus = "good";
				$healthStatusColor = "info";
				$healthMessage = "Anggaran Anda dalam kondisi baik.";
			} elseif ($healthScore >= 40) {
				$healthStatus = "fair";
				$healthStatusColor = "warning";
				$healthMessage = "Anggaran Anda perlu perhatian.";
			} else {
				$healthStatus = "poor";
				$healthStatusColor = "danger";
				$healthMessage = "Anggaran Anda dalam kondisi kritis!";
			}

			// Get attention budgets
			$attentionBudgets = $budgets
				->filter(fn($b) => ($b->percentage ?? 0) >= 80)
				->sortByDesc("percentage")
				->take(3);

			return [
				"exceeded" => $exceeded,
				"warning" => $warning,
				"moderate" => $moderate,
				"good" => $good,
				"total_expense_categories" => $totalExpenseCategories,
				"total_budgeted" => $budgetedCategories,
				"unbudgeted_categories" => $unbudgetedCategories,
				"health_score" => $healthScore,
				"health_status" => $healthStatus,
				"health_status_color" => $healthStatusColor,
				"health_message" => $healthMessage,
				"attention_budgets" => $attentionBudgets,
				"has_attention_items" => $attentionBudgets->count() > 0,
				"period" => Budget::MONTH_NAMES[$month] . " " . $year,
				"month" => $month,
				"year" => $year,
			];
		});
	}

	/**
	 * Check if budget exists for category in period
	 */
	public function existsForCategory(
		User $user,
		int $categoryId,
		int $month,
		int $year
	): bool {
		return $this->model
			->where("user_id", $user->id)
			->where("category_id", $categoryId)
			->forPeriod($month, $year)
			->exists();
	}

	/**
	 * Cache key generators
	 */
	private function getUserBudgetsCacheKey(
		int $userId,
		int $month,
		int $year
	): string {
		return "user_budgetss_{$userId}_{$month}_{$year}";
	}

	private function getBudgetSummaryCacheKey(
		int $userId,
		int $month,
		int $year
	): string {
		return "budget_summary_{$userId}_{$month}_{$year}";
	}

	private function getHealthStatusCacheKey(
		int $userId,
		int $month,
		int $year
	): string {
		return "budget_health_{$userId}_{$month}_{$year}";
	}

	/**
	 * Cache invalidation methods
	 */
	private function invalidateUserBudgetCache(
		int $userId,
		int $month,
		int $year
	): void {
		Cache::forget($this->getUserBudgetsCacheKey($userId, $month, $year));
	}

	private function invalidateBudgetSummaryCache(
		int $userId,
		int $month,
		int $year
	): void {
		Cache::forget($this->getBudgetSummaryCacheKey($userId, $month, $year));
		Cache::forget($this->getHealthStatusCacheKey($userId, $month, $year));
	}

	/**
	 * Get user budgets with summary in single call
	 */
	public function getUserBudgetsWithSummary(
		User $user,
		array $filters = []
	): array {
		$month = $filters["month"] ?? Carbon::now()->month;
		$year = $filters["year"] ?? Carbon::now()->year;

		$cacheKey = "user_budgets_summary_{$user->id}_{$month}_{$year}";

		return Cache::remember($cacheKey, self::CACHE_DURATION, function () use (
			$user,
			$filters,
			$month,
			$year
		) {
			$budgets = $this->getUserBudgets($user, $filters);
			$summary = $this->getBudgetSummary($user, $month, $year);
			$health = $this->getBudgetHealthStatus($user, $month, $year);

			return [
				"budgets" => $budgets,
				"summary" => $summary,
				"health" => $health,
			];
		});
	}
}
