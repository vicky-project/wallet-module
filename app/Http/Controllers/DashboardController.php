<?php

namespace Modules\Wallet\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Wallet\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Wallet\Models\Account;
use Modules\Wallet\Models\Budget;
use Modules\Wallet\Models\Transaction;

class DashboardController extends Controller
{
	public function __construct(protected DashboardService $dashboardService)
	{
	}

	public function index(Request $request)
	{
		$user = $request->user();

		//$dashboardData = $this->dashboardService->getDashboardData($user);

		//return view("wallet::index", compact("dashboardData"));

		// Total saldo semua akun
		$totalBalance = Account::where("user_id", $user->id)
			->where("is_active", true)
			->sum("balance");

		// Daftar akun aktif
		$accounts = Account::where("user_id", $user->id)
			->where("is_active", true)
			->orderBy("is_default", "desc")
			->orderBy("name")
			->get();

		// Transaksi terbaru (10)
		$recentTransactions = Transaction::with(["category", "account"])
			->where("user_id", $user->id)
			->orderBy("transaction_date", "desc")
			->limit(10)
			->get();

		// Pengeluaran per kategori bulan ini (untuk progress bar sederhana)
		$currentMonth = now()->startOfMonth();
		$expensesByCategory = Transaction::where("user_id", $user->id)
			->where("type", "expense")
			->where("transaction_date", ">=", $currentMonth)
			->select("category_id", DB::raw("SUM(amount) as total"))
			->groupBy("category_id")
			->with("category")
			->get();

		// Anggaran bulan ini (opsional)
		$budgets = Budget::with("category")
			->where("user_id", $user->id)
			->where("year", now()->year)
			->where("period_type", "monthly")
			->where("period_value", now()->month)
			->get();

		return view(
			"financial.index",
			compact(
				"totalBalance",
				"accounts",
				"recentTransactions",
				"expensesByCategory",
				"budgets"
			)
		);
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
