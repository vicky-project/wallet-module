<?php

namespace Modules\Wallet\Services;

use App\Models\User;
use Carbon\Carbon;
use Modules\Wallet\Models\Account;
use Modules\Wallet\Models\Budget;
use Modules\Wallet\Models\Category;
use Modules\Wallet\Repositories\BudgetRepository;
use Modules\Wallet\Repositories\CategoryRepository;
use Modules\Wallet\Enums\PeriodType;
use Modules\Wallet\Enums\CategoryType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BudgetService
{
	/**
	 * @var BudgetRepository
	 */
	protected $budgetRepository;

	/**
	 * @var CategoryRepository
	 */
	protected $categoryRepository;

	/**
	 * @param BudgetRepository $budgetRepository
	 * @param CategoryRepository $categoryRepository
	 */
	public function __construct(
		BudgetRepository $budgetRepository,
		CategoryRepository $categoryRepository
	) {
		$this->budgetRepository = $budgetRepository;
		$this->categoryRepository = $categoryRepository;
	}

	/**
	 * Get dashboard data for budgets
	 */
	public function getDashboardData(User $user, Carbon $now): array
	{
		$data = $this->budgetRepository->getDashboardData($user, $now);

		return [
			"stats" => $data["stats"] ?? [],
			"summary" => $data["summary"] ?? [],
			"warnings" => $data["warnings"] ?? [],
		];
	}

	/**
	 * Calculate average budget usage
	 */
	public function calculateAverageBudgetUsage(array $budgetData): float
	{
		if (empty($budgetData["summary"])) {
			return 0;
		}

		$totalUsage = array_sum(
			array_column($budgetData["summary"], "usage_percentage")
		);
		return $totalUsage / count($budgetData["summary"]);
	}

	/**
	 * Get all budgets with filters
	 */
	public function getIndexData(array $filters = [])
	{
		$user = auth()->user();
		$stats = $this->budgetRepository->getBudgetStats($user);
		$budgets = $this->budgetRepository->getPaginatedBudgets(15, $filters);

		return [
			"budgets" => $budgets,
			"stats" => $stats,
			"filters" => $filters,
		];
	}

	/**
	 * Get data for create form
	 */
	public function getCreateData(): array
	{
		$user = auth()->user();

		// Get expense categories
		$categories = $this->categoryRepository->getByType(
			CategoryType::EXPENSE,
			$user
		);

		// Get active accounts
		$accounts = Account::where("user_id", $user->id)
			->where("is_active", true)
			->orderBy("name")
			->get();

		// Get period types
		$periodTypes = PeriodType::cases();

		// Get current date info for default values
		$currentMonth = date("m");
		$currentYear = date("Y");

		return [
			"categories" => $categories,
			"accounts" => $accounts,
			"periodTypes" => $periodTypes,
			"defaultPeriodType" => PeriodType::MONTHLY,
			"defaultPeriodValue" => $currentMonth,
			"defaultYear" => $currentYear,
		];
	}

	/**
	 * Create a new budget
	 */
	public function createBudget(User $user, array $data): Budget
	{
		try {
			return $this->budgetRepository->createBudget($data, $user);
		} catch (\Exception $e) {
			throw new \RuntimeException(
				"Failed to create budget: " . $e->getMessage()
			);
		}
	}

	/**
	 * Update an existing budget
	 */
	public function updateBudget(Budget $budget, array $data): Budget
	{
		$user = auth()->user();

		// Check authorization
		if ($budget->user_id !== $user->id) {
			throw new \Illuminate\Auth\Access\AuthorizationException(
				"You are not authorized to update this budget"
			);
		}

		try {
			return $this->budgetRepository->updateBudget($budget, $data);
		} catch (\Exception $e) {
			throw new \RuntimeException(
				"Failed to update budget: " . $e->getMessage()
			);
		}
	}

	/**
	 * Delete a budget
	 */
	public function deleteBudget(Budget $budget): bool
	{
		$user = auth()->user();

		// Check authorization
		if ($budget->user_id !== $user->id) {
			throw new \Illuminate\Auth\Access\AuthorizationException(
				"You are not authorized to delete this budget"
			);
		}

		try {
			return $this->budgetRepository->deleteBudget($budget);
		} catch (\Exception $e) {
			throw new \RuntimeException(
				"Failed to delete budget: " . $e->getMessage()
			);
		}
	}

	/**
	 * Toggle budget active status
	 */
	public function toggleStatus(Budget $budget): Budget
	{
		$user = auth()->user();

		// Check authorization
		if ($budget->user_id !== $user->id) {
			throw new \Illuminate\Auth\Access\AuthorizationException(
				"You are not authorized to modify this budget"
			);
		}

		$budget->is_active = !$budget->is_active;
		$budget->save();

		// Invalidate cache
		$this->budgetRepository->invalidateUserBudgetCache($user->id);

		return $budget;
	}

	/**
	 * Get budget statistics for dashboard
	 */
	public function getDashboardSummary(): array
	{
		$user = auth()->user();
		return $this->budgetRepository->getDashboardSummary($user);
	}

	/**
	 * Update spent amounts for all budgets
	 */
	public function updateAllSpentAmounts(): void
	{
		$user = auth()->user();
		$this->budgetRepository->updateAllSpentAmounts($user);
	}

	/**
	 * Create next period budget
	 */
	public function createNextPeriodBudget(Budget $budget): Budget
	{
		$user = auth()->user();

		// Check authorization
		if ($budget->user_id !== $user->id) {
			throw new \Illuminate\Auth\Access\AuthorizationException(
				"You are not authorized to modify this budget"
			);
		}

		return $this->budgetRepository->createNextPeriodBudget($budget);
	}

	/**
	 * Get budgets for category
	 */
	public function getBudgetsForCategory(Category $category): Collection
	{
		$user = auth()->user();

		// Check authorization
		if ($category->user_id !== $user->id) {
			throw new \Illuminate\Auth\Access\AuthorizationException(
				"You are not authorized to view these budgets"
			);
		}

		return $this->budgetRepository->getBudgetsByCategory($category);
	}

	/**
	 * Get expiring budgets
	 */
	public function getExpiringBudgets(int $days = 7): Collection
	{
		$user = auth()->user();
		return $this->budgetRepository->getExpiringBudgets($user, $days);
	}

	/**
	 * Bulk update budgets
	 */
	public function bulkUpdate(array $budgetIds, array $data): int
	{
		$user = auth()->user();

		// Validate data for bulk update
		$validatedData = validator($data, [
			"is_active" => ["boolean"],
		])->validate();

		try {
			DB::beginTransaction();

			$count = Budget::where("user_id", $user->id)
				->whereIn("id", $budgetIds)
				->update($validatedData);

			DB::commit();

			// Invalidate cache
			$this->budgetRepository->invalidateUserBudgetCache($user->id);

			return $count;
		} catch (\Exception $e) {
			DB::rollBack();
			throw new \RuntimeException(
				"Failed to bulk update budgets: " . $e->getMessage()
			);
		}
	}

	/**
	 * Calculate period dates for form
	 */
	public function calculatePeriodDates(
		string $periodType,
		int $periodValue,
		int $year
	): array {
		return $this->budgetRepository->calculatePeriodDates(
			$periodType,
			$periodValue,
			$year
		);
	}

	/**
	 * Get suggested budget amount for category
	 */
	public function getSuggestedAmount(int $categoryId): ?int
	{
		$user = auth()->user();
		$category = Category::where("user_id", $user->id)
			->where("id", $categoryId)
			->where("type", CategoryType::EXPENSE)
			->first();

		if (!$category) {
			return null;
		}

		// Get last 3 months average spending
		$threeMonthsAgo = now()->subMonths(3);

		$averageSpent = $category
			->transactions()
			->where("type", "expense")
			->where("transaction_date", ">=", $threeMonthsAgo)
			->avg("amount");

		return $averageSpent ? (int) round($averageSpent) : 500000; // Default 500k
	}

	public function calculateNextPeriod(Budget $budget)
	{
		return $this->budgetRepository->calculateNextPeriod($budget);
	}

	public function getChartData(
		Budget $budget,
		?Carbon $startDate = null,
		?Carbon $endDate = null
	): array {
		$startDate = $startDate ?? Carbon::now()->subDays(6);
		$endDate = $endDate ?? Carbon::now();

		if ($startDate < $budget->start_date) {
			$startDate = $budget->start_date;
		}

		if ($endDate > $budget->end_date) {
			$endDate = $budget->end_date;
		}

		$dailySpent = $this->budgetRepository->getBudgetData(
			$budget,
			$startDate,
			$endDate
		);

		dd($dailySpent);
		return $this->formatChartData($dailySpent, $startDate, $endDate, $budget);
	}

	private function formatChartData(
		array $dailySpent,
		Carbon $startDate,
		Carbon $endDate,
		Budget $budget
	): array {
		$labels = [];
		$data = [];

		$currentDate = $startDate->copy();
		$dayNames = ["Ming", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"];

		while ($currentDate <= $endDate) {
			$dayIndex = $currentDate->dayOfWeek;
			$dayName = $dayNames[$dayIndex];

			$labels[] = $dayName . " (" . $currentDate->format("d/m") . ")";

			$dateString = $currentDate->toDateString();
			$amount = isset($dailySpent[$dateString])
				? $dailySpent[$dateString]["total"]
				: 0;
			$data[] = (int) $amount;

			$currentDate->addDay();
		}

		$daysInPeriod = $budget->days_left > 0 ? $budget->days_left : 1;
		$dailyBudgetTarget =
			(int) ($budget->amount->getAmount()->toInt() / $daysInPeriod);

		return [
			"labels" => $labels,
			"datasets" => [
				[
					"label" => "Pengeluaran Harian",
					"data" => $data,
					"backgroundColor" => "rgba(13, 110, 253, 0.1)",
					"borderColor" => "rgba(13, 110, 253, 1)",
					"borderWidth" => 2,
					"fill" => true,
					"tension" => 0.4,
				],
				[
					"label" => "Target Budget harian",
					"data" => array_fill(0, count($labels), $dailyBudgetTarget),
					"borderColor" => "rgba(40, 167, 69, 0.7)",
					"borderWidth" => 1.5,
					"borderDash" => [5, 5],
					"fill" => false,
					"pointRadius" => 0,
					"tension" => 0,
				],
			],
			"budget_info" => [
				"total_budget" => $budget->amount->getAmount()->toInt(),
				"total_spent" => $budget->spent->getAmount()->toInt(),
				"remaining" => $budget->remaining,
				"usage_percentage" => $budget->usage_percentage,
				"daily_budget" => (int) $budget->daily_budget,
				"period_label" => $budget->period_label,
				"days_left" => $budget->days_left,
			],
		];
	}
}
