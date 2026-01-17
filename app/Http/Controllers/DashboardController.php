<?php

namespace Modules\Wallet\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Wallet\Services\TransactionService;
use Modules\Wallet\Services\AccountService;
use Modules\Wallet\Services\BudgetService;
use Modules\Wallet\Services\CategoryService;
use Modules\Wallet\Services\RecurringTransactionService;
use Carbon\Carbon;

class DashboardController extends Controller
{
	protected $transactionService;
	protected $accountService;
	protected $budgetService;
	protected $categoryService;
	protected $recurringService;

	public function __construct(
		TransactionService $transactionService,
		AccountService $accountService,
		BudgetService $budgetService,
		CategoryService $categoryService,
		RecurringTransactionService $recurringService
	) {
		$this->transactionService = $transactionService;
		$this->accountService = $accountService;
		$this->budgetService = $budgetService;
		$this->categoryService = $categoryService;
		$this->recurringService = $recurringService;
	}

	public function index()
	{
		$user = auth()->user();
		$now = Carbon::now();
		$startOfMonth = $now->copy()->startOfMonth();
		$endOfMonth = $now->copy()->endOfMonth();

		// 1. Data Transaksi
		$transactionSummary = $this->transactionService->getDashboardSummary($user);
		$transactionStats = $this->transactionService->getTransactionStats($user);

		// 2. Data Akun
		$accounts = $this->accountService
			->getRepository()
			->getUserAccounts($user, ["is_active" => true]);
		$accountSummary = $this->accountService->getAccountSummary($user);
		$accountAnalytics = $this->accountService->getAccountAnalytics(
			$user,
			$startOfMonth,
			$endOfMonth
		);

		// 3. Data Budget
		$budgetSummary = $this->budgetService->getDashboardSummary();
		$budgetWarnings = $this->categoryService->getBudgetWarnings();

		// 4. Data Kategori
		$categoryStats = $this->categoryService->getCategoryStats();
		$categoryAnalysis = $this->getCategoryAnalysis($user);

		// 5. Transaksi Rutin Mendatang
		$upcomingRecurring = $this->recurringService->getUpcomingTransactions(7);
		dd($upcomingRecurring);

		// 6. Transaksi Terbaru
		$recentTransactions = $this->getRecentTransactions($user);

		// 7. Data Grafik Bulanan
		$monthlyChartData = $this->getMonthlyChartData($user);

		// 8. Aktivitas Terakhir
		$recentActivity = $this->getRecentActivity($user);

		// 9. Peringatan Akun
		$accountAlerts = $this->getAccountAlerts($accounts);

		$dashboardData = [
			"total_balance" => $accountSummary["total_balance"] ?? 0,
			"balance_trend" => $this->calculateBalanceTrend($user),
			"monthly_income" =>
				$transactionSummary["transaction_summary"]["monthly_income"] ?? 0,
			"monthly_expense" =>
				$transactionSummary["transaction_summary"]["monthly_expense"] ?? 0,
			"income_count" => $transactionStats["stats"]["income_count"] ?? 0,
			"expense_count" => $transactionStats["stats"]["expense_count"] ?? 0,
			"budget_usage_percentage" => $this->calculateAverageBudgetUsage(
				$budgetSummary
			),
			"budget_stats" => $budgetSummary["stats"] ?? [],
			"budget_summary" => $budgetSummary["budgets"] ?? [],
			"budget_warnings" => $budgetWarnings,
			"account_stats" => [
				"total" => $accounts->count(),
				"active" => $accounts->where("is_active", true)->count(),
				"total_balance" => $accountSummary["total_balance"] ?? 0,
			],
			"accounts" => $accounts->map(function ($account) use ($accountAnalytics) {
				$analytics = collect($accountAnalytics)->firstWhere(
					"account.id",
					$account->id
				);
				return [
					"id" => $account->id,
					"name" => $account->name,
					"type" => $account->type,
					"balance" => $account->balance,
					"color" => $account->color,
					"icon" => $account->icon,
					"is_default" => $account->is_default,
					"net_flow" => $analytics["net_flow"] ?? 0,
				];
			}),
			"category_analysis" => $categoryAnalysis,
			"transaction_stats" => [
				"total_this_month" =>
					$transactionStats["stats"]["current_month_count"] ?? 0,
				"today" => $transactionStats["stats"]["today_total"] ?? 0,
				"last_7_days" => $transactionStats["stats"]["last_7_days"] ?? 0,
				"last_30_days" => $transactionStats["stats"]["last_30_days"] ?? 0,
			],
			"recent_transactions" => $recentTransactions,
			"upcoming_recurring" => $upcomingRecurring,
			"monthly_chart" => $monthlyChartData,
			"recent_activity" => $recentActivity,
			"account_alerts" => $accountAlerts,
		];

		return view("wallet::index", compact("dashboardData"));
	}

	public function refresh()
	{
		$user = auth()->user();

		// Hanya ambil data yang perlu di-refresh
		$transactionSummary = $this->transactionService->getDashboardSummary($user);
		$accountSummary = $this->accountService->getAccountSummary($user);

		return response()->json([
			"total_balance" => $accountSummary["total_balance"] ?? 0,
			"monthly_income" =>
				$transactionSummary["transaction_summary"]["monthly_income"] ?? 0,
			"monthly_expense" =>
				$transactionSummary["transaction_summary"]["monthly_expense"] ?? 0,
			"updated_at" => now()->format("H:i:s"),
		]);
	}

	private function getCategoryAnalysis($user)
	{
		$categories = $this->categoryService->getCategoriesWithMonthlyTotals();
		$totalExpense = $categories->where("type", "expense")->sum("monthly_total");

		return $categories
			->where("type", "expense")
			->map(function ($category) use ($totalExpense) {
				$percentage =
					$totalExpense > 0
						? ($category->monthly_total / $totalExpense) * 100
						: 0;
				$colors = [
					"#4361ee",
					"#3f37c9",
					"#4cc9f0",
					"#f72585",
					"#f8961e",
					"#7209b7",
					"#3a0ca3",
					"#4361ee",
				];

				return [
					"name" => $category->name,
					"amount" => $category->monthly_total,
					"percentage" => round($percentage, 1),
					"color" => $colors[rand(0, count($colors) - 1)],
				];
			})
			->sortByDesc("amount")
			->values()
			->toArray();
	}

	private function getRecentTransactions($user)
	{
		$transactions = $this->transactionService->transactionRepository->getPaginatedTransactions(
			["limit" => 10],
			10
		);

		return collect($transactions["transactions"] ?? [])
			->map(function ($transaction) {
				return [
					"id" => $transaction->id,
					"description" => $transaction->description,
					"amount" => $transaction->amount,
					"type" => $transaction->type,
					"category_name" =>
						$transaction->category->name ?? "Tidak Berkategori",
					"icon" => $transaction->category->icon ?? "bi-cash",
					"date" => $transaction->transaction_date,
					"is_recurring" => $transaction->is_recurring,
				];
			})
			->toArray();
	}

	private function getMonthlyChartData($user)
	{
		$months = [];
		$incomeData = [];
		$expenseData = [];

		for ($i = 5; $i >= 0; $i--) {
			$date = Carbon::now()->subMonths($i);
			$months[] = $date->format("M");

			// Query untuk pemasukan bulan ini
			$income = $this->transactionService->transactionRepository
				->getModel()
				->where("user_id", $user->id)
				->where("type", "income")
				->whereMonth("transaction_date", $date->month)
				->whereYear("transaction_date", $date->year)
				->sum("amount");

			// Query untuk pengeluaran bulan ini
			$expense = $this->transactionService->transactionRepository
				->getModel()
				->where("user_id", $user->id)
				->where("type", "expense")
				->whereMonth("transaction_date", $date->month)
				->whereYear("transaction_date", $date->year)
				->sum("amount");

			$incomeData[] = $income ?? 0;
			$expenseData[] = $expense ?? 0;
		}

		return [
			"labels" => $months,
			"income" => $incomeData,
			"expense" => $expenseData,
		];
	}

	private function getRecentActivity($user)
	{
		$activities = [];

		// Transaksi hari ini
		$todayCount = $this->transactionService->transactionRepository
			->getModel()
			->where("user_id", $user->id)
			->whereDate("transaction_date", today())
			->count();

		if ($todayCount > 0) {
			$activities[] = [
				"icon" => "bi-receipt",
				"description" => "{$todayCount} transaksi hari ini",
				"time_ago" => "Hari ini",
			];
		}

		// Budget yang akan berakhir
		$expiringBudgets = $this->budgetService->getExpiringBudgets(3);
		if ($expiringBudgets->count() > 0) {
			$activities[] = [
				"icon" => "bi-calendar-x",
				"description" => "{$expiringBudgets->count()} budget akan berakhir",
				"time_ago" => "Segera",
			];
		}

		// Transaksi rutin diproses
		$processed = $this->recurringService->processDueRecurringTransactions();
		if ($processed["processed"] > 0) {
			$activities[] = [
				"icon" => "bi-arrow-repeat",
				"description" => "{$processed["processed"]} transaksi rutin diproses",
				"time_ago" => "Baru saja",
			];
		}

		return $activities;
	}

	private function getAccountAlerts($accounts)
	{
		$alerts = [];

		foreach ($accounts as $account) {
			// Alert untuk saldo rendah
			if (
				$account->balance->getAmount()->toInt() < 100000 &&
				$account->type !== "liability"
			) {
				$alerts[] = [
					"message" =>
						"Saldo {$account->name} rendah: " .
						number_format($account->balance->getAmount()->toInt()) .
						" IDR",
					"level" => "warning",
				];
			}

			// Alert untuk akun tidak aktif
			if (!$account->is_active) {
				$alerts[] = [
					"message" => "Akun {$account->name} tidak aktif",
					"level" => "info",
				];
			}
		}

		return $alerts;
	}

	private function calculateBalanceTrend($user)
	{
		$currentMonth = $this->transactionService->transactionRepository
			->getModel()
			->where("user_id", $user->id)
			->where("type", "income")
			->whereMonth("transaction_date", now()->month)
			->whereYear("transaction_date", now()->year)
			->sum("amount");

		$lastMonth = $this->transactionService->transactionRepository
			->getModel()
			->where("user_id", $user->id)
			->where("type", "income")
			->whereMonth("transaction_date", now()->subMonth()->month)
			->whereYear("transaction_date", now()->subMonth()->year)
			->sum("amount");

		if ($lastMonth > 0) {
			return (($currentMonth - $lastMonth) / $lastMonth) * 100;
		}

		return 0;
	}

	private function calculateAverageBudgetUsage($budgetSummary)
	{
		if (!isset($budgetSummary["budgets"]) || empty($budgetSummary["budgets"])) {
			return 0;
		}

		$totalPercentage = 0;
		$count = 0;

		foreach ($budgetSummary["budgets"] as $budget) {
			if (isset($budget["amount"]) && $budget["amount"] > 0) {
				$percentage = min(100, ($budget["spent"] / $budget["amount"]) * 100);
				$totalPercentage += $percentage;
				$count++;
			}
		}

		return $count > 0 ? round($totalPercentage / $count, 1) : 0;
	}
}
