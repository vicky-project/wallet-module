<?php

namespace Modules\Wallet\Http\Controllers;

use Modules\Core\Http\Controllers\BaseController;
use Modules\Wallet\Repositories\{
	TransactionRepository,
	CategoryRepository,
	BudgetRepository,
	SavingGoalRepository,
	AccountRepository
};
use Brick\Money\Money;
use Brick\Math\RoundingMode;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends BaseController
{
	protected $transactionRepository;
	protected $categoryRepository;
	protected $budgetRepository;
	protected $savingGoalRepository;
	protected $accountRepository;

	public function __construct(
		TransactionRepository $transactionRepository,
		CategoryRepository $categoryRepository,
		BudgetRepository $budgetRepository,
		SavingGoalRepository $savingGoalRepository,
		AccountRepository $accountRepository
	) {
		$this->transactionRepository = $transactionRepository;
		$this->categoryRepository = $categoryRepository;
		$this->budgetRepository = $budgetRepository;
		$this->savingGoalRepository = $savingGoalRepository;
		$this->accountRepository = $accountRepository;
	}

	/**
	 * Display main dashboard
	 */
	public function index()
	{
		$user = auth()->user();
		$currentMonth = Carbon::now()->month;
		$currentYear = Carbon::now()->year;

		// Get dashboard statistics
		$stats = $this->getDashboardStats($user, $currentMonth, $currentYear);

		// Get recent transactions
		$recentTransactions = $this->transactionRepository->getRecentTransactions(
			$user,
			10
		);

		// Get budget summary for current month
		$budgetSummary = $this->budgetRepository->getBudgetSummary(
			$user,
			$currentMonth,
			$currentYear
		);

		$budgets = $this->budgetRepository->getUserBudgets($user);

		// Get active saving goals
		$savingGoals = $this->savingGoalRepository->getActiveGoals($user);

		// Get accounts summary
		$accounts = $this->accountRepository->getAccountsMapping(
			$this->accountRepository->accounts(auth()->user())
		);

		// Get categories for quick add forms
		$incomeCategories = $this->categoryRepository->getByType("income", $user);
		$expenseCategories = $this->categoryRepository->getByType("expense", $user);

		return view(
			"wallet::index",
			compact(
				"stats",
				"recentTransactions",
				"budgets",
				"budgetSummary",
				"savingGoals",
				"accounts",
				"incomeCategories",
				"expenseCategories"
			)
		);
	}

	/**
	 * Get dashboard statistics
	 */
	private function getDashboardStats($user, $month, $year): array
	{
		// Get transaction summary
		$transactionSummary = $this->transactionRepository->getSummary(
			$user,
			$month,
			$year
		);

		// Get total account balance
		$totalBalance = $this->accountRepository->getTotalBalance($user);

		// Get saving goals progress
		$savingGoals = $this->savingGoalRepository->getActiveGoals($user);
		$savingsProgress = $this->calculateSavingsProgress($savingGoals);

		// Calculate monthly comparison
		$lastMonth = Carbon::now()->subMonth();
		$lastMonthSummary = $this->transactionRepository->getSummary(
			$user,
			$lastMonth->month,
			$lastMonth->year
		);

		// Calculate percentage changes
		$incomeChange = $this->calculatePercentageChange(
			$transactionSummary["income"],
			$lastMonthSummary["income"]
		);

		$expenseChange = $this->calculatePercentageChange(
			$transactionSummary["expense"],
			$lastMonthSummary["expense"]
		);

		$netChange = $this->calculatePercentageChange(
			$transactionSummary["net_balance"],
			$lastMonthSummary["net_balance"]
		);

		return [
			"monthly_income" => [
				"amount" => $transactionSummary["income"],
				"change" => $incomeChange,
				"formatted" => $transactionSummary["income"]->formatTo("id_ID"),
				"change_formatted" => $this->formatPercentage($incomeChange),
				"is_positive" => $incomeChange >= 0,
			],
			"monthly_expense" => [
				"amount" => $transactionSummary["expense"],
				"change" => $expenseChange,
				"formatted" => $transactionSummary["expense"]->formatTo("id_ID"),
				"change_formatted" => $this->formatPercentage($expenseChange),
				"is_positive" => $expenseChange <= 0, // Negative change is good for expenses
			],
			"net_balance" => [
				"amount" => $transactionSummary["net_balance"],
				"change" => $netChange,
				"formatted" => $transactionSummary["net_balance"]->formatTo("id_ID"),
				"change_formatted" => $this->formatPercentage($netChange),
				"is_positive" => $netChange >= 0,
			],
			"total_balance" => [
				"amount" => $totalBalance,
				"formatted" => $totalBalance->formatTo("id_ID"),
				"accounts_count" => $this->accountRepository
					->getModel()
					->where("user_id", $user->id)
					->count(),
			],
			"savings_progress" => [
				"percentage" => $savingsProgress,
				"formatted" => number_format($savingsProgress, 1) . "%",
				"goals_count" => $savingGoals->count(),
				"completed_goals" => $savingGoals->where("is_completed", true)->count(),
			],
			"budget_status" => $this->budgetRepository->getBudgetSummary(
				$user,
				$month,
				$year
			),
		];
	}

	/**
	 * Calculate savings progress
	 */
	private function calculateSavingsProgress($savingGoals): float
	{
		if ($savingGoals->isEmpty()) {
			return 0;
		}

		$totalTarget = 0;
		$totalCurrent = 0;

		foreach ($savingGoals as $goal) {
			$totalTarget += $goal->target_amount;
			$totalCurrent += $goal->current_amount;
		}

		if ($totalTarget > 0) {
			return ($totalCurrent / $totalTarget) * 100;
		}

		return 0;
	}

	/**
	 * Calculate percentage change
	 */
	private function calculatePercentageChange(
		Money $current,
		Money $previous
	): float {
		if ($previous->isZero()) {
			return $current->isPositive() ? 100 : ($current->isZero() ? 0 : -100);
		}

		$change = $current->minus($previous);
		$percentage = $change
			->dividedBy($previous->getAmount()->toInt())
			->multipliedBy(100);

		return $percentage->getMinorAmount()->toFloat();
	}

	/**
	 * Calculate budget usage percentage
	 */
	private function calculateBudgetUsage(Money $budget, Money $spent): float
	{
		if ($budget->isZero()) {
			return 0;
		}

		$percentage = $spent
			->getAmount()
			->dividedBy($budget->getAmount())
			->multipliedBy(100);

		return min(100, $percentage->toFloat());
	}

	/**
	 * Format percentage for display
	 */
	private function formatPercentage(float $percentage): string
	{
		$prefix = $percentage >= 0 ? "+" : "";
		return $prefix . number_format($percentage, 1) . "%";
	}

	/**
	 * Get quick stats for AJAX requests
	 */
	public function quickStats(Request $request)
	{
		$user = auth()->user();
		$currentMonth = Carbon::now()->month;
		$currentYear = Carbon::now()->year;

		$stats = $this->getDashboardStats($user, $currentMonth, $currentYear);

		return response()->json([
			"success" => true,
			"data" => [
				"monthly_income" => $stats["monthly_income"],
				"monthly_expense" => $stats["monthly_expense"],
				"net_balance" => $stats["net_balance"],
				"total_balance" => $stats["total_balance"],
				"savings_progress" => $stats["savings_progress"],
			],
		]);
	}

	/**
	 * Get recent transactions for AJAX
	 */
	public function recentTransactions(Request $request)
	{
		$user = auth()->user();
		$transactions = $this->transactionRepository->getRecentTransactions(
			$user,
			10
		);

		return response()->json([
			"success" => true,
			"data" => $transactions->map(function ($transaction) {
				return [
					"id" => $transaction->id,
					"title" => $transaction->title,
					"description" => $transaction->description,
					"amount" => $transaction->formatted_amount ?? $transaction->amount,
					"type" => $transaction->type,
					"category" => $transaction->category->name,
					"category_color" => $transaction->category->color,
					"category_icon" => $transaction->category->icon,
					"date" => $transaction->transaction_date->format("d M Y"),
					"is_income" => $transaction->type === "income",
				];
			}),
		]);
	}

	/**
	 * Get budget summary for AJAX
	 */
	public function budgetSummary(Request $request)
	{
		$user = auth()->user();
		$currentMonth = Carbon::now()->month;
		$currentYear = Carbon::now()->year;

		$budgetSummary = $this->budgetRepository->getBudgetSummary(
			$user,
			$currentMonth,
			$currentYear
		);

		return response()->json([
			"success" => true,
			"data" => [
				"budgets" => $budgetSummary["budgets"]->map(function ($budget) {
					return [
						"id" => $budget->id,
						"category_name" => $budget->category->name,
						"category_color" => $budget->category->color,
						"amount" => $budget->formatted_amount ?? $budget->amount,
						"spent" => $budget->formatted_spent ?? $budget->spent,
						"remaining" =>
							$budget->remaining ?? $budget->amount - $budget->spent,
						"percentage" => $budget->percentage,
						"status" => $budget->status,
						"status_color" => $budget->status_color,
						"is_exceeded" => $budget->is_exceeded,
					];
				}),
				"summary" => [
					"total_budget" => $budgetSummary["total_budget"]->formatTo("id_ID"),
					"total_spent" => $budgetSummary["total_spent"]->formatTo("id_ID"),
					"total_remaining" => $budgetSummary["total_remaining"]->formatTo(
						"id_ID"
					),
					"budget_usage_percentage" => round(
						$budgetSummary["budget_usage_percentage"],
						1
					),
				],
			],
		]);
	}

	/**
	 * Get saving goals for AJAX
	 */
	public function savingGoals(Request $request)
	{
		$user = auth()->user();
		$savingGoals = $this->savingGoalRepository->getActiveGoals($user);

		return response()->json([
			"success" => true,
			"data" => $savingGoals->map(function ($goal) {
				return [
					"id" => $goal->id,
					"name" => $goal->name,
					"target_amount" =>
						$goal->formatted_target_amount ?? $goal->target_amount,
					"current_amount" =>
						$goal->formatted_current_amount ?? $goal->current_amount,
					"remaining_amount" =>
						$goal->remaining_amount ??
						$goal->target_amount - $goal->current_amount,
					"progress_percentage" => $goal->progress_percentage,
					"target_date" => $goal->target_date->format("d M Y"),
					"days_remaining" => $goal->days_remaining,
					"priority" => $goal->priority,
					"priority_label" => $goal->priority_label,
					"priority_color" => $goal->priority_color,
					"status" => $goal->status,
					"status_label" => $goal->status_label,
					"is_on_track" => $goal->is_on_track,
				];
			}),
		]);
	}

	/**
	 * Get accounts summary for AJAX
	 */
	public function accountsSummary(Request $request)
	{
		$user = auth()->user();
		$accounts = $this->accountRepository->getActiveAccounts($user);
		$byTypeSummary = $this->accountRepository->getByTypeWithSummary($user);

		return response()->json([
			"success" => true,
			"data" => [
				"accounts" => $accounts->map(function ($account) {
					return [
						"id" => $account->id,
						"name" => $account->name,
						"type" => $account->type,
						"type_label" => $account->type_label,
						"current_balance" =>
							$account->formatted_current_balance ?? $account->current_balance,
						"initial_balance" =>
							$account->formatted_initial_balance ?? $account->initial_balance,
						"balance_change" => $account->balance_change,
						"is_positive_change" => $account->is_positive_change,
						"icon" => $account->icon,
						"color" => $account->color,
						"is_active" => $account->is_active,
					];
				}),
				"by_type" => $byTypeSummary,
				"total_balance" => $this->accountRepository
					->getTotalBalance($user)
					->formatTo("id_ID"),
			],
		]);
	}

	/**
	 * Quick add transaction from FAB
	 */
	public function quickAddTransaction(Request $request)
	{
		$request->validate([
			"type" => "required|in:income,expense",
			"amount" => "required|numeric|min:1000",
			"category_id" => "required|exists:categories,id",
			"account_id" => "required|exists:accounts,id",
			"title" => "nullable|string|max:255",
			"description" => "nullable|string",
		]);

		$user = auth()->user();

		// Verify category belongs to user and matches type
		$category = $this->categoryRepository->find($request->category_id);
		if (
			$category->user_id !== $user->id ||
			$category->type !== $request->type
		) {
			return response()->json(
				[
					"success" => false,
					"message" => "Kategori tidak valid",
				],
				422
			);
		}

		// Verify account belongs to user
		$account = $this->accountRepository->find($request->account_id);
		if ($account->user_id !== $user->id) {
			return response()->json(
				[
					"success" => false,
					"message" => "Akun tidak valid",
				],
				422
			);
		}

		// For expense, check account balance
		if ($request->type === "expense") {
			$amount = Money::of($request->amount, "IDR");
			if (
				!$this->accountRepository->hasSufficientBalance($account->id, $amount)
			) {
				return response()->json(
					[
						"success" => false,
						"message" => "Saldo akun tidak mencukupi",
					],
					422
				);
			}
		}

		$data = [
			"title" =>
				$request->title ?:
				($request->type === "income"
					? "Pemasukan Cepat"
					: "Pengeluaran Cepat"),
			"amount" => $request->amount,
			"type" => $request->type,
			"category_id" => $request->category_id,
			"account_id" => $request->account_id,
			"description" => $request->description,
			"payment_method" => "cash",
			"transaction_date" => now(),
		];

		try {
			$transaction = $this->transactionRepository->createTransaction(
				$data,
				$user
			);

			// If expense, update budget spent amount
			if ($request->type === "expense") {
				$budget = $this->budgetRepository->model
					->where("user_id", $user->id)
					->where("category_id", $request->category_id)
					->where("month", now()->month)
					->where("year", now()->year)
					->first();

				if ($budget) {
					$this->budgetRepository->updateSpentAmount($budget->id);
				}
			}

			return response()->json([
				"success" => true,
				"message" => "Transaksi berhasil dicatat",
				"data" => [
					"id" => $transaction->id,
					"title" => $transaction->title,
					"amount" => $transaction->formatted_amount ?? $transaction->amount,
					"type" => $transaction->type,
					"date" => $transaction->transaction_date->format("d M Y"),
				],
			]);
		} catch (\Exception $e) {
			return response()->json(
				[
					"success" => false,
					"message" => "Gagal mencatat transaksi: " . $e->getMessage(),
				],
				500
			);
		}
	}

	/**
	 * Update user theme preference
	 */
	public function updateTheme(Request $request)
	{
		$request->validate([
			"theme" => "required|in:light,dark",
		]);

		$user = auth()->user();
		$user->theme = $request->theme;
		$user->save();

		return response()->json([
			"success" => true,
			"message" => "Tema berhasil diubah",
			"theme" => $user->theme,
		]);
	}
}
