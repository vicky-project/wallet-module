<?php

namespace Modules\Wallet\Services;

use App\Models\User;
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
use Carbon\Carbon;

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
	 * Get all budgets with filters
	 */
	public function getIndexData(array $filters = [])
	{
		$user = auth()->user();
		$stats = $this->budgetRepository->getBudgetStats($user);
		$budgets = $this->budgetRepository->getPaginatedBudgets(15, $filters);
		dd($budgets);

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
	public function createBudget(array $data): Budget
	{
		$user = auth()->user();

		// Validate data
		$this->validateBudgetData($data, $user);

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

		// Validate data
		$this->validateBudgetData($data, $user, $budget->id);

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
	 * Validate budget data
	 */
	private function validateBudgetData(
		array $data,
		User $user,
		?int $exceptId = null
	): void {
		$rules = [
			"category_id" => [
				"required",
				"exists:categories,id,user_id," . $user->id . ",type,expense",
			],
			"name" => ["nullable", "string", "max:100"],
			"period_type" => [
				"required",
				"in:" . implode(",", array_column(PeriodType::cases(), "value")),
			],
			"period_value" => ["required", "integer", "min:1"],
			"year" => ["required", "integer", "min:2000", "max:2100"],
			"start_date" => ["required", "date"],
			"end_date" => ["required", "date", "after_or_equal:start_date"],
			"amount" => ["required", "integer", "min:1000"],
			"rollover_unused" => ["boolean"],
			"rollover_limit" => ["nullable", "integer", "min:0"],
			"is_active" => ["boolean"],
			"accounts" => ["array"],
			"accounts.*" => ["exists:accounts,id,user_id," . $user->id],
		];

		$validator = validator($data, $rules);

		if ($validator->fails()) {
			throw ValidationException::withMessages($validator->errors()->toArray());
		}
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
}
