<?php

namespace Modules\Wallet\Http\Controllers;

use Modules\Core\Http\Controllers\BaseController;
use Modules\Wallet\Services\{AccountService};
use Brick\Money\Money;
use Brick\Math\RoundingMode;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends BaseController
{
	protected AccountService $accountService;

	public function __construct(AccountService $accountService)
	{
		$this->accountService = $accountService;
	}

	/**
	 * Display main dashboard
	 */
	public function index(Request $request)
	{
		$user = $request->user();

		// Get accounts summary
		$accountSummary = $this->accountService->getAccountSummary($user);

		$accountTypeDistribution = $this->accountService->getAccountTypeDistribution(
			$user
		);

		// Get account analytics for current month
		$currentMonth = $request->period ?? date("m");
		$currentYear = date("Y");
		$accountAnalytics = $this->accountService->getAccountAnalytics(
			$user,
			$currentMonth,
			$currentYear
		);

		// Calculate total income and expense from analytics
		$totalIncome = 0;
		$totalExpense = 0;
		foreach ($accountAnalytics as $analytic) {
			$totalIncome += $analytic["income"]->getAmount()->toInt();
			$totalExpense += $analytic["expense"]->getAmount()->toInt();
		}

		// Get recent transactions if service exists
		$recentTransactions = [];
		$totalTransactions = 0;

		// Calculate net cash flow
		$netCashFlow = $totalIncome - $totalExpense;

		// Get popular accounts (most used)
		$popularAccounts = $this->accountService
			->getRepository()
			->getPopularAccounts($user, 3);

		// Get monthly trends (last 6 months)
		$monthlyTrends = $this->getMonthlyTrends($user, 6);

		return view(
			"wallet::index",
			compact(
				"accountSummary",
				"accountTypeDistribution",
				"accountAnalytics",
				"totalIncome",
				"totalExpense",
				"netCashFlow",
				"popularAccounts",
				"monthlyTrends"
			)
		);
	}

	/**
	 * Get monthly trends for charts
	 */
	private function getMonthlyTrends($user, $months = 6, $year = null): array
	{
		$year = $year ?? date("Y");
		$month = date("m");

		$trends = [];

		for ($i = $months - 1; $i >= 0; $i--) {
			$trendMonth = $month - $i;
			$trendYear = $year;

			if ($trendMonth <= 0) {
				$trendMonth += 12;
				$trendYear -= 1;
			}

			$analytics = $this->accountService->getAccountAnalytics(
				$user,
				$trendMonth,
				$trendYear
			);

			$monthIncome = 0;
			$monthExpense = 0;

			foreach ($analytics as $analytic) {
				$monthIncome += $analytic["income"]->getAmount()->toInt();
				$monthExpense += $analytic["expense"]->getAmount()->toInt();
			}

			$monthName = Carbon::create($trendYear, $trendMonth, 1)->format("M Y");

			$trends[] = [
				"month" => $monthName,
				"month_number" => $trendMonth,
				"year" => $trendYear,
				"income" => $monthIncome,
				"expense" => $monthExpense,
				"net_flow" => $monthIncome - $monthExpense,
			];
		}

		return $trends;
	}
}
