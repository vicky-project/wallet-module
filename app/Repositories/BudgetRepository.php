<?php

namespace Modules\Wallet\Repositories;

use App\Models\User;
use Brick\Money\Money;
use Carbon\Carbon;
use Modules\Wallet\Models\Budget;
use Illuminate\Support\Collection;

class BudgetRepository extends BaseRepository
{
	public function __construct(Budget $model)
	{
		parent::__construct($model);
	}

	/**
	 * Create budget with Money amount
	 */
	public function createBudget(array $data, User $user): Budget
	{
		$data["user_id"] = $user->id;

		// Convert amounts to Money
		$data["amount"] = $this->toDatabaseAmount($this->toMoney($data["amount"]));

		// Set default month/year if not provided
		if (!isset($data["month"])) {
			$data["month"] = Carbon::now()->month;
		}

		if (!isset($data["year"])) {
			$data["year"] = Carbon::now()->year;
		}

		return $this->create($data);
	}

	/**
	 * Update budget
	 */
	public function updateBudget(int $id, array $data): Budget
	{
		if (isset($data["amount"])) {
			$data["amount"] = $this->toDatabaseAmount(
				$this->toMoney($data["amount"])
			);
		}

		$this->update($id, $data);
		return $this->find($id);
	}

	/**
	 * Get current month's budget
	 */
	public function getCurrentBudget(User $user): Collection
	{
		$currentMonth = Carbon::now()->month;
		$currentYear = Carbon::now()->year;

		return $this->model
			->with("category")
			->where("user_id", $user->id)
			->where("month", $currentMonth)
			->where("year", $currentYear)
			->get()
			->map(function ($budget) {
				$budget->formatted_amount = $this->formatMoney(
					$this->fromDatabaseAmount($budget->amount)
				);
				$budget->formatted_spent = $this->formatMoney(
					$this->fromDatabaseAmount($budget->spent)
				);
				$budget->remaining = $this->formatMoney(
					$this->fromDatabaseAmount($budget->amount)->minus(
						$this->fromDatabaseAmount($budget->spent)
					)
				);
				return $budget;
			});
	}

	/**
	 * Get all budgets for user
	 */
	public function getUserBudgets(User $user, array $filters = []): Collection
	{
		$query = $this->model->with("category")->where("user_id", $user->id);

		if (isset($filters["month"])) {
			$query->where("month", $filters["month"]);
		}

		if (isset($filters["year"])) {
			$query->where("year", $filters["year"]);
		}

		if (isset($filters["category_id"])) {
			$query->where("category_id", $filters["category_id"]);
		}

		return $query
			->orderBy("year", "desc")
			->orderBy("month", "desc")
			->get();
	}

	/**
	 * Update spent amount for budget
	 */
	public function updateSpentAmount(int $budgetId): Budget
	{
		$budget = $this->find($budgetId);

		// Calculate total spent from transactions
		$totalSpent = $budget->category
			->transactions()
			->where("type", "expense")
			->whereMonth("transaction_date", $budget->month)
			->whereYear("transaction_date", $budget->year)
			->sum("amount");

		$budget->spent = $totalSpent;
		$budget->save();

		return $budget;
	}

	/**
	 * Get budget summary
	 */
	public function getBudgetSummary(
		User $user,
		int $month = null,
		int $year = null
	): array {
		$month = $month ?? date("m");
		$year = $year ?? date("Y");

		$budgets = $this->model
			->with("category")
			->where("user_id", $user->id)
			->where("month", $month)
			->where("year", $year)
			->get();

		$totalBudget = $budgets->sum("amount");
		$totalSpent = $budgets->sum("spent");

		return [
			"budgets" => $budgets,
			"total_budget" => $this->fromDatabaseAmount($totalBudget),
			"total_spent" => $this->fromDatabaseAmount($totalSpent),
			"total_remaining" => $this->fromDatabaseAmount($totalBudget)->minus(
				$this->fromDatabaseAmount($totalSpent)
			),
			"budget_usage_percentage" =>
				$totalBudget > 0 ? ($totalSpent / $totalBudget) * 100 : 0,
			"formatted_total_budget" => $this->formatMoney(
				$this->fromDatabaseAmount($totalBudget)
			),
			"formatted_total_spent" => $this->formatMoney(
				$this->fromDatabaseAmount($totalSpent)
			),
			"formatted_total_remaining" => $this->formatMoney(
				$this->fromDatabaseAmount($totalBudget)->minus(
					$this->fromDatabaseAmount($totalSpent)
				)
			),
		];
	}
}
