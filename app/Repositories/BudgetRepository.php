<?php

namespace Modules\Wallet\Repositories;

use App\Models\User;
use Brick\Money\Money;
use Carbon\Carbon;
use Modules\Wallet\Models\Budget;
use Modules\Wallet\Models\Category;
use Modules\Wallet\Models\Transaction;
use Modules\Wallet\Enums\CategoryType;
use Modules\Wallet\Enums\TransactionType;
use Illuminate\Support\Collection;

class BudgetRepository extends BaseRepository
{
	public function __construct(Budget $model)
	{
		$this->model = $model;
	}

	/**
	 * Create budget - HANYA UNTUK KATEGORI EXPENSE
	 */
	public function createBudget(array $data, User $user): Budget
	{
		// Validasi kategori harus expense
		$category = Category::find($data["category_id"]);
		if (!$category || $category->type !== "expense") {
			throw new \Exception(
				"Anggaran hanya dapat dibuat untuk kategori pengeluaran"
			);
		}

		$data["user_id"] = $user->id;

		// Convert amount to minor units
		if (isset($data["amount"])) {
			$data["amount"] = (int) ($data["amount"] * 100); // Convert to minor units
		}

		// Set default month/year if not provided
		if (!isset($data["month"])) {
			$data["month"] = Carbon::now()->month;
		}

		if (!isset($data["year"])) {
			$data["year"] = Carbon::now()->year;
		}

		// Set default spent to 0
		$data["spent"] = 0;
		$data["is_active"] = true;

		return $this->create($data);
	}

	/**
	 * Update budget
	 */
	public function updateBudget(int $id, array $data): Budget
	{
		if (isset($data["amount"])) {
			$data["amount"] = (int) ($data["amount"] * 100); // Convert to minor units
		}

		$this->update($id, $data);
		return $this->find($id);
	}

	/**
	 * Get current month's budget - HANYA KATEGORI EXPENSE
	 */
	public function getCurrentBudget(User $user): Collection
	{
		$currentMonth = Carbon::now()->month;
		$currentYear = Carbon::now()->year;

		return $this->model
			->with([
				"category" => function ($query) {
					$query->expense();
				},
			])
			->where("user_id", $user->id)
			->forPeriod($currentMonth, $currentYear)
			->active()
			->get()
			->each(function ($budget) {
				// Update spent amount dari transaksi
				$budget->updateSpentAmount();
			});
	}

	/**
	 * Get budget summary - PERHITUNGAN YANG BENAR
	 */
	public function getBudgetSummary(
		User $user,
		int $month = null,
		int $year = null
	): array {
		$month = $month ?? date("m");
		$year = $year ?? date("Y");

		// Dapatkan semua budget aktif untuk periode tertentu
		$budgets = $this->model
			->with([
				"category" => function ($query) {
					$query->expense();
				},
			])
			->where("user_id", $user->id)
			->where("is_active", true)
			->where("month", $month)
			->where("year", $year)
			->get()
			->each(function ($budget) {
				// Update spent amount sebelum perhitungan
				$budget->updateSpentAmount();
			});

		// Hitung total
		$totalBudget = $budgets->sum(function ($budget) {
			return $budget->amount->getAmount()->toInt();
		});

		$totalSpent = $budgets->sum(function ($budget) {
			return $budget->spent->getAmount()->toInt();
		});

		$totalRemaining = max(0, $totalBudget - $totalSpent);

		$budgetUsagePercentage =
			$totalBudget > 0 ? round(($totalSpent / $totalBudget) * 100, 2) : 0;

		// Dapatkan kategori expense yang TIDAK memiliki budget
		$unbudgetedCategories = Category::expense()
			->forUser($user->id)
			->whereDoesntHave("budgets", function ($query) use ($month, $year) {
				$query
					->where("month", $month)
					->where("year", $year)
					->where("is_active", true);
			})
			->get();

		// Hitung pengeluaran pada kategori tanpa budget
		$unbudgetedExpenses = 0;
		foreach ($unbudgetedCategories as $category) {
			$categoryExpenses = Transaction::where("user_id", $user->id)
				->where("category_id", $category->id)
				->where("type", TransactionType::EXPENSE)
				->whereMonth("transaction_date", $month)
				->whereYear("transaction_date", $year)
				->sum("amount");

			$unbudgetedExpenses += $categoryExpenses;
		}

		// Hitung total pengeluaran bulan ini (semua kategori expense)
		$totalExpenses = Transaction::where("user_id", $user->id)
			->where("type", TransactionType::EXPENSE)
			->whereMonth("transaction_date", $month)
			->whereYear("transaction_date", $year)
			->sum("amount");

		// Persentase pengeluaran yang ter-budget
		$budgetedExpensePercentage =
			$totalExpenses > 0 ? round(($totalSpent / $totalExpenses) * 100, 2) : 0;

		return [
			"budgets" => $budgets,
			"total_budget" => $totalBudget / 100,
			"total_spent" => $totalSpent / 100,
			"total_remaining" => $totalRemaining / 100,
			"budget_usage_percentage" => $budgetUsagePercentage,
			"formatted_total_budget" =>
				"Rp " . number_format($totalBudget / 100, 0, ",", "."),
			"formatted_total_spent" =>
				"Rp " . number_format($totalSpent / 100, 0, ",", "."),
			"formatted_total_remaining" =>
				"Rp " . number_format($totalRemaining / 100, 0, ",", "."),
			"unbudgeted_categories" => $unbudgetedCategories,
			"unbudgeted_expenses" => $unbudgetedExpenses / 100,
			"formatted_unbudgeted_expenses" =>
				"Rp " . number_format($unbudgetedExpenses / 100, 0, ",", "."),
			"total_expenses" => $totalExpenses / 100,
			"formatted_total_expenses" =>
				"Rp " . number_format($totalExpenses / 100, 0, ",", "."),
			"budgeted_expense_percentage" => $budgetedExpensePercentage,
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

		$budgets = $this->model
			->where("user_id", $user->id)
			->where("month", $month)
			->where("year", $year)
			->get();

		foreach ($budgets as $budget) {
			$budget->updateSpentAmount();
		}
	}

	/**
	 * Update spent amounts when a transaction is created/updated
	 */
	public function updateSpentOnTransaction(Transaction $transaction): void
	{
		if ($transaction->type !== TransactionType::EXPENSE) {
			return; // Hanya update untuk transaksi expense
		}

		$month = $transaction->transaction_date->month;
		$year = $transaction->transaction_date->year;

		// Cari budget untuk kategori ini pada periode transaksi
		$budget = $this->model
			->where("user_id", $transaction->user_id)
			->where("category_id", $transaction->category_id)
			->where("month", $month)
			->where("year", $year)
			->where("is_active", true)
			->first();

		if ($budget) {
			$budget->updateSpentAmount();
		}
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

		// Dapatkan pengeluaran bulan sebelumnya per kategori
		$previousExpenses = Transaction::where("user_id", $user->id)
			->where("type", TransactionType::EXPENSE)
			->whereMonth("transaction_date", $previousMonth)
			->whereYear("transaction_date", $previousYear)
			->selectRaw("category_id, SUM(amount) as total_spent")
			->groupBy("category_id")
			->get()
			->keyBy("category_id");

		$suggestions = [];

		foreach ($previousExpenses as $categoryId => $expense) {
			$category = Category::find($categoryId);
			if ($category && $category->type === CategoryType::EXPENSE) {
				$suggestedAmount = round($expense->total_spent / 100); // Convert to IDR

				// Tambahkan buffer 10% untuk bulan depan
				$suggestedAmount = $suggestedAmount * 1.1;

				$suggestions[] = [
					"category_id" => $categoryId,
					"category_name" => $category->name,
					"previous_spent" => $expense->total_spent / 100,
					"suggested_amount" => $suggestedAmount,
					"formatted_suggested_amount" =>
						"Rp " . number_format($suggestedAmount, 0, ",", "."),
				];
			}
		}

		return $suggestions;
	}

	/**
	 * Check budget health status
	 */
	public function getBudgetHealthStatus(
		User $user,
		int $month = null,
		int $year = null
	): array {
		$month = $month ?? Carbon::now()->month;
		$year = $year ?? date("Y");

		$budgets = $this->model
			->with("category")
			->where("user_id", $user->id)
			->where("month", $month)
			->where("year", $year)
			->where("is_active", true)
			->get()
			->each(function ($budget) {
				$budget->updateSpentAmount();
			});

		$exceeded = $budgets->filter(fn($b) => $b->isExceeded)->count();
		$warning = $budgets->filter(fn($b) => $b->status === "warning")->count();
		$moderate = $budgets->filter(fn($b) => $b->status === "moderate")->count();
		$good = $budgets->filter(fn($b) => $b->status === "good")->count();

		$totalCategories = Category::where("type", CategoryType::EXPENSE)
			->where(function ($query) use ($user) {
				$query->where("user_id", $user->id)->orWhereNull("user_id");
			})
			->count();

		$budgetedCategories = $budgets->count();
		$unbudgetedCategories = $totalCategories - $budgetedCategories;

		return [
			"exceeded" => $exceeded,
			"warning" => $warning,
			"moderate" => $moderate,
			"good" => $good,
			"total_budgeted" => $budgetedCategories,
			"unbudgeted_categories" => $unbudgetedCategories,
			"health_score" =>
				$totalCategories > 0
					? round(
						(($good * 1 + $moderate * 0.7 + $warning * 0.4 - $exceeded * 0.5) /
							$totalCategories) *
							100,
						0
					)
					: 0,
		];
	}
}
