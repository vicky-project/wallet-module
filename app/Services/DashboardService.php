<?php

namespace Modules\Wallet\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class DashboardService
{
	protected $accountService;
	protected $transactionService;
	protected $budgetService;
	protected $categoryService;
	protected $recurringService;

	public function __construct(
		AccountService $accountService,
		TransactionService $transactionService,
		BudgetService $budgetService,
		CategoryService $categoryService,
		RecurringTransactionService $recurringService
	) {
		$this->accountService = $accountService;
		$this->transactionService = $transactionService;
		$this->budgetService = $budgetService;
		$this->categoryService = $categoryService;
		$this->recurringService = $recurringService;
	}

	public function getDashboardData(User $user): array
	{
		try {
			$now = Carbon::now();

			// Gunakan Parallel Processing jika memungkinkan
			$results = $this->executeQueriesWithFallback($user, $now);
			//dd($results);

			// Format data untuk response
			return $this->formatDashboardData($results, $user);
		} catch (\Exception $e) {
			return $this->getMinimalDashboardData($user);
		}
	}

	protected function executeQueriesWithFallback(User $user, Carbon $now): array
	{
		// Menggunakan Laravel's Collection untuk parallel processing (PHP 7.4+)
		$services = [
			"accounts" => fn() => $this->accountService->getDashboardData(
				$user,
				$now
			),
			"transactions" => fn() => $this->transactionService->getDashboardData(
				$user,
				$now
			),
			"budgets" => fn() => $this->budgetService->getDashboardData($user, $now),
			"categories" => fn() => $this->categoryService->getDashboardData(
				$user,
				$now
			),
			"recurring" => fn() => $this->recurringService->getDashboardData(
				$user,
				$now
			),
		];

		$results = [];
		foreach ($services as $key => $service) {
			try {
				$results[$key] = $service();
			} catch (\Exception $e) {
				$results[$key] = $this->getFallbackData($key);
			}
		}

		return $results;
	}

	protected function getFallbackData(string $serviceKey): array
	{
		switch ($serviceKey) {
			case "accounts":
				return [
					"accounts" => [],
					"analytics" => [],
					"stats" => ["total" => 0, "active" => 0, "total_balance" => 0],
					"alerts" => [],
				];
			case "transactions":
				return [
					"monthly_income" => 0,
					"monthly_expense" => 0,
					"income_count" => 0,
					"expense_count" => 0,
					"stats" => [
						"today_total" => 0,
						"last_7_days" => 0,
						"last_30_days" => 0,
						"current_month_count" => 0,
					],
					"recent" => [],
					"chart_data" => [],
				];
			case "budgets":
				return [
					"stats" => [],
					"summary" => [],
					"warnings" => [],
				];
			case "categories":
				return [
					"analysis" => [],
					"stats" => [],
				];
			case "recurring":
				return [
					"upcoming" => [],
				];
			default:
				return [];
		}
	}

	protected function getMinimalDashboardData(User $user): array
	{
		return [
			"total_balance" => 0,
			"balance_trend" => 0,
			"monthly_income" => 0,
			"monthly_expense" => 0,
			"income_count" => 0,
			"expense_count" => 0,
			"budget_usage_percentage" => 0,
			"budget_stats" => [],
			"budget_summary" => [],
			"budget_warnings" => [],
			"account_stats" => [
				"total" => 0,
				"active" => 0,
				"total_balance" => 0,
			],
			"accounts" => [],
			"category_analysis" => [],
			"transaction_stats" => [
				"total_this_month" => 0,
				"today" => 0,
				"last_7_days" => 0,
				"last_30_days" => 0,
			],
			"recent_transactions" => [],
			"upcoming_recurring" => [],
			"monthly_chart" => [],
			"recent_activity" => [],
			"account_alerts" => [],
		];
	}

	protected function formatDashboardData(array $data, User $user): array
	{
		return [
			"total_balance" => $data["accounts"]["stats"]["total_balance"] ?? 0,
			"balance_trend" => $this->calculateBalanceTrend($user),
			"monthly_income" => $data["transactions"]["monthly_income"] ?? 0,
			"monthly_expense" => $data["transactions"]["monthly_expense"] ?? 0,
			"income_count" => $data["transactions"]["income_count"] ?? 0,
			"expense_count" => $data["transactions"]["expense_count"] ?? 0,
			"budget_usage_percentage" => $this->calculateAverageBudgetUsage(
				$data["budgets"]
			),
			"budget_stats" => $data["budgets"]["stats"] ?? [],
			"budget_summary" => $data["budgets"]["summary"] ?? [],
			"budget_warnings" => $data["budgets"]["warnings"] ?? [],
			"account_stats" => [
				"total" => $data["accounts"]["stats"]["total"] ?? 0,
				"active" => $data["accounts"]["stats"]["active"] ?? 0,
				"total_balance" => $data["accounts"]["stats"]["total_balance"] ?? 0,
			],
			"accounts" => $this->formatAccountsData($data["accounts"]),
			"category_analysis" => $data["categories"]["analysis"] ?? [],
			"transaction_stats" => $this->formatTransactionStats(
				$data["transactions"]
			),
			"recent_transactions" => $data["transactions"]["recent"] ?? [],
			"upcoming_recurring" => $data["recurring"]["upcoming"] ?? [],
			"monthly_chart" => $data["transactions"]["chart_data"] ?? [],
			"recent_activity" => $this->getRecentActivity($user),
			"account_alerts" => $data["accounts"]["alerts"] ?? [],
		];
	}

	protected function formatAccountsData(array $accountData): Collection
	{
		return collect($accountData["accounts"] ?? [])->map(function (
			$account
		) use ($accountData) {
			$analytics = collect($accountData["analytics"] ?? [])->firstWhere(
				"account.id",
				$account["id"]
			);
			dd($analytics);

			return [
				"id" => $account["id"],
				"name" => $account["name"],
				"type" => $account["type"],
				"balance" => $account["balance"],
				"color" => $account["color"],
				"icon" => $account["icon"],
				"is_default" => $account["is_default"],
				"net_flow" => $analytics["net_flow"] ?? 0,
			];
		});
	}

	protected function formatTransactionStats(array $transactionData): array
	{
		return [
			"total_this_month" =>
				$transactionData["stats"]["current_month_count"] ?? 0,
			"today" => $transactionData["stats"]["today_total"] ?? 0,
			"last_7_days" => $transactionData["stats"]["last_7_days"] ?? 0,
			"last_30_days" => $transactionData["stats"]["last_30_days"] ?? 0,
		];
	}

	protected function calculateBalanceTrend(User $user): float
	{
		return $this->accountService->calculateBalanceTrend($user);
	}

	protected function calculateAverageBudgetUsage(array $budgetData): float
	{
		return $this->budgetService->calculateAverageBudgetUsage($budgetData);
	}

	protected function getRecentActivity(User $user): array
	{
		return $this->transactionService->getRecentActivity($user);
	}
}
