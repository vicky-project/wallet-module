<?php

namespace Modules\Wallet\Repositories;

use Modules\Wallet\Models\Transaction;
use Modules\Wallet\Models\Account;
use Modules\Wallet\Models\Category;
use Modules\Wallet\Models\Budget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Brick\Money\Money;

class ReportRepository
{
	protected $transaction;
	protected $account;
	protected $category;
	protected $budget;

	public function __construct(
		Transaction $transaction,
		Account $account,
		Category $category,
		Budget $budget
	) {
		$this->transaction = $transaction;
		$this->account = $account;
		$this->category = $category;
		$this->budget = $budget;
	}

	public function getFinancialSummary(array $params): array
	{
		$userId = $params["user_id"];
		$startDate = $params["start_date"] ?? now()->startOfMonth();
		$endDate = $params["end_date"] ?? now()->endOfMonth();
		$accountId = $params["account_id"] ?? null;

		$query = $this->transaction
			->where("user_id", $userId)
			->whereBetween("transaction_date", [$startDate, $endDate]);

		if ($accountId) {
			$query->where("account_id", $accountId);
		}

		$data = $query
			->select(
				DB::raw(
					"SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income"
				),
				DB::raw(
					"SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense"
				),
				DB::raw("COUNT(CASE WHEN type = 'income' THEN 1 END) as income_count"),
				DB::raw("COUNT(CASE WHEN type = 'expense' THEN 1 END) as expense_count")
			)
			->first();

		$totalTransfer = $this->transaction
			->where("user_id", $userId)
			->whereBetween("transaction_date", [$startDate, $endDate])
			->where("type", "transfer")
			->sum("amount");

		return [
			"total_income" => (int) ($data->total_income ?? 0),
			"total_expense" => (int) ($data->total_expense ?? 0),
			"net_flow" => Money::ofMinor(
				(int) ($data->total_income ?? 0),
				config("wallet.default_currency", "USD")
			)
				->minus((int) ($data->total_expense ?? 0))
				->getAmount()
				->toInt(),
			"income_count" => (int) ($data->income_count ?? 0),
			"expense_count" => (int) ($data->expense_count ?? 0),
			"total_transfer" => (int) $totalTransfer,
			"period" => [
				"start_date" => $startDate,
				"end_date" => $endDate,
			],
		];
	}

	public function getIncomeExpenseTrend(array $params): array
	{
		$userId = $params["user_id"];
		$startDate = $params["start_date"] ?? now()->subMonths(6);
		$endDate = $params["end_date"] ?? now();
		$groupBy = $params["group_by"] ?? "month"; // day, week, month

		$query = $this->transaction
			->where("user_id", $userId)
			->whereBetween("transaction_date", [$startDate, $endDate])
			->whereIn("type", ["income", "expense"]);

		switch ($groupBy) {
			case "day":
				$query
					->select(
						DB::raw("DATE(transaction_date) as period"),
						DB::raw(
							"SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income"
						),
						DB::raw(
							"SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense"
						)
					)
					->groupBy("period")
					->orderBy("period");
				break;

			case "week":
				$query
					->select(
						DB::raw("YEARWEEK(transaction_date, 1) as period"),
						DB::raw("MIN(DATE(transaction_date)) as period_start"),
						DB::raw(
							"SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income"
						),
						DB::raw(
							"SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense"
						)
					)
					->groupBy("period")
					->orderBy("period");
				break;

			case "month":
			default:
				$query
					->select(
						DB::raw("DATE_FORMAT(transaction_date, '%Y-%m') as period"),
						DB::raw("MIN(DATE(transaction_date)) as period_start"),
						DB::raw(
							"SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income"
						),
						DB::raw(
							"SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense"
						)
					)
					->groupBy("period")
					->orderBy("period");
				break;
		}

		$results = $query->get();

		$labels = [];
		$incomeData = [];
		$expenseData = [];

		foreach ($results as $row) {
			if ($groupBy === "month") {
				$labels[] = Carbon::parse($row->period_start)->format("M Y");
			} else {
				$labels[] = $row->period;
			}
			$incomeData[] = (int) $row->income;
			$expenseData[] = (int) $row->expense;
		}

		return [
			"labels" => $labels,
			"datasets" => [
				[
					"label" => "Pendapatan",
					"data" => $incomeData,
					"borderColor" => "#10b981",
					"backgroundColor" => "rgba(16, 185, 129, 0.1)",
					"tension" => 0.4,
				],
				[
					"label" => "Pengeluaran",
					"data" => $expenseData,
					"borderColor" => "#ef4444",
					"backgroundColor" => "rgba(239, 68, 68, 0.1)",
					"tension" => 0.4,
				],
			],
		];
	}

	public function getCategoryAnalysis(array $params): array
	{
		$userId = $params["user_id"];
		$startDate = $params["start_date"] ?? now()->startOfMonth();
		$endDate = $params["end_date"] ?? now()->endOfMonth();
		$type = $params["type"] ?? "expense"; // income or expense
		$limit = $params["limit"] ?? 10;

		$results = $this->transaction
			->where("user_id", $userId)
			->where("type", $type)
			->whereBetween("transaction_date", [$startDate, $endDate])
			->with("category")
			->select("category_id", DB::raw("SUM(amount) as total"))
			->groupBy("category_id")
			->orderByDesc("total")
			->limit($limit)
			->get();

		$labels = [];
		$data = [];
		$backgroundColors = [];
		$colors = [
			"#3b82f6",
			"#10b981",
			"#f59e0b",
			"#ef4444",
			"#8b5cf6",
			"#06b6d4",
			"#84cc16",
			"#f97316",
			"#6366f1",
			"#ec4899",
		];

		foreach ($results as $index => $item) {
			$labels[] = $item->category->name ?? "Tidak Berkategori";
			$data[] = (int) $item->total;
			$backgroundColors[] = $colors[$index % count($colors)];
		}

		// Add "Other" category if there are more categories
		$otherTotal = $this->transaction
			->where("user_id", $userId)
			->where("type", $type)
			->whereBetween("transaction_date", [$startDate, $endDate])
			->whereNotIn("category_id", $results->pluck("category_id"))
			->sum("amount");

		if ($otherTotal > 0) {
			$labels[] = "Lainnya";
			$data[] = (int) $otherTotal;
			$backgroundColors[] = "#9ca3af";
		}

		return [
			"labels" => $labels,
			"datasets" => [
				[
					"label" =>
						$type === "income"
							? "Pendapatan per Kategori"
							: "Pengeluaran per Kategori",
					"data" => $data,
					"backgroundColor" => $backgroundColors,
					"borderWidth" => 1,
				],
			],
		];
	}

	public function getBudgetAnalysis(array $params): array
	{
		$userId = $params["user_id"];
		$period = $params["period"] ?? "current"; // current, monthly, yearly
		$limit = $params["limit"] ?? 10;

		$query = $this->budget
			->with("category")
			->where("user_id", $userId)
			->where("is_active", true);

		if ($period === "current") {
			$query->where("start_date", "<=", now())->where("end_date", ">=", now());
		} elseif ($period === "monthly") {
			$query
				->where("period_type", "monthly")
				->where("year", now()->year)
				->where("period_value", now()->month);
		}

		$budgets = $query
			->orderByDesc("amount")
			->limit($limit)
			->get();

		$labels = [];
		$budgetData = [];
		$spentData = [];
		$remainingData = [];
		$usageColors = [];

		foreach ($budgets as $budget) {
			$labels[] = $budget->category->name ?? "Tidak Berkategori";
			$budgetAmount = $budget->amount->getMinorAmount()->toInt();
			$spentAmount = $budget->spent->getMinorAmount()->toInt();

			$budgetData[] = $budgetAmount;
			$spentData[] = $spentAmount;
			$remainingData[] = max(0, $budgetAmount - $spentAmount);

			// Color based on usage percentage
			$percentage =
				$budgetAmount > 0 ? ($spentAmount / $budgetAmount) * 100 : 0;
			if ($percentage >= 90) {
				$usageColors[] = "#ef4444";
			} elseif ($percentage >= 70) {
				$usageColors[] = "#f59e0b";
			} else {
				$usageColors[] = "#10b981";
			}
		}

		return [
			"labels" => $labels,
			"datasets" => [
				[
					"label" => "Anggaran",
					"data" => $budgetData,
					"backgroundColor" => "rgba(59, 130, 246, 0.5)",
					"borderColor" => "#3b82f6",
					"borderWidth" => 1,
				],
				[
					"label" => "Terpakai",
					"data" => $spentData,
					"backgroundColor" => $usageColors,
					"borderColor" => array_map(fn($color) => $color, $usageColors),
					"borderWidth" => 1,
				],
			],
			"summary" => [
				"total_budget" => array_sum($budgetData),
				"total_spent" => array_sum($spentData),
				"total_remaining" => array_sum($remainingData),
			],
		];
	}

	public function getAccountAnalysis(array $params): array
	{
		$userId = $params["user_id"];
		$showInactive = $params["show_inactive"] ?? false;

		$query = $this->account->where("user_id", $userId);

		if (!$showInactive) {
			$query->where("is_active", true);
		}

		$accounts = $query->orderByDesc("balance")->get();

		$labels = [];
		$balanceData = [];
		$backgroundColors = [];

		foreach ($accounts as $account) {
			$labels[] = $account->name;
			$balanceData[] = $account->balance->getMinorAmount()->toInt();
			$backgroundColors[] = $account->color;
		}

		$totalBalance = array_sum($balanceData);

		return [
			"labels" => $labels,
			"datasets" => [
				[
					"label" => "Saldo Akun",
					"data" => $balanceData,
					"backgroundColor" => $backgroundColors,
					"borderWidth" => 1,
				],
			],
			"summary" => [
				"total_balance" => $totalBalance,
				"account_count" => count($accounts),
			],
		];
	}

	public function getTransactionAnalysis(array $params): array
	{
		$userId = $params["user_id"];
		$startDate = $params["start_date"] ?? now()->startOfMonth();
		$endDate = $params["end_date"] ?? now()->endOfMonth();

		$transactions = $this->transaction
			->where("user_id", $userId)
			->whereBetween("transaction_date", [$startDate, $endDate])
			->whereIn("type", ["income", "expense"])
			->select(
				DB::raw("DAYOFWEEK(transaction_date) as day_of_week"),
				DB::raw("COUNT(*) as transaction_count"),
				DB::raw(
					"SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income"
				),
				DB::raw(
					"SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense"
				)
			)
			->groupBy("day_of_week")
			->orderBy("day_of_week")
			->get();

		$days = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
		$labels = [];
		$incomeData = array_fill(0, 7, 0);
		$expenseData = array_fill(0, 7, 0);
		$countData = array_fill(0, 7, 0);

		foreach ($transactions as $transaction) {
			$dayIndex = $transaction->day_of_week - 1; // MySQL returns 1-7, array uses 0-6
			$incomeData[$dayIndex] = (int) $transaction->income;
			$expenseData[$dayIndex] = (int) $transaction->expense;
			$countData[$dayIndex] = (int) $transaction->transaction_count;
		}

		return [
			"labels" => $days,
			"datasets" => [
				[
					"label" => "Pendapatan",
					"data" => $incomeData,
					"backgroundColor" => "rgba(16, 185, 129, 0.5)",
					"borderColor" => "#10b981",
					"borderWidth" => 1,
				],
				[
					"label" => "Pengeluaran",
					"data" => $expenseData,
					"backgroundColor" => "rgba(239, 68, 68, 0.5)",
					"borderColor" => "#ef4444",
					"borderWidth" => 1,
				],
			],
			"summary" => [
				"avg_daily_transactions" => array_sum($countData) / 7,
				"most_active_day" => $days[array_search(max($countData), $countData)],
				"least_active_day" => $days[array_search(min($countData), $countData)],
			],
		];
	}
}
