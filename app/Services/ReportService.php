<?php

namespace Modules\Wallet\Services;

use Modules\Wallet\Repositories\ReportRepository;
use Illuminate\Support\Facades\Cache;
use Brick\Money\Money;
use Brick\Money\Currency;

class ReportService
{
	protected $reportRepository;

	public function __construct(ReportRepository $reportRepository)
	{
		$this->reportRepository = $reportRepository;
	}

	public function getDashboardSummary(int $userId, array $filters = []): array
	{
		$cacheKey = "dashboard_summary_{$userId}_" . md5(json_encode($filters));

		return Cache::remember($cacheKey, 300, function () use ($userId, $filters) {
			$params = array_merge(["user_id" => $userId], $filters);

			$financialSummary = $this->reportRepository->getFinancialSummary($params);

			$incomeExpenseTrend = $this->reportRepository->getIncomeExpenseTrend(
				$params
			);
			$categoryAnalysis = $this->reportRepository->getCategoryAnalysis($params);
			$budgetAnalysis = $this->reportRepository->getBudgetAnalysis($params);
			$accountAnalysis = $this->reportRepository->getAccountAnalysis($params);

			return [
				"financial_summary" => $this->formatMoneyValues($financialSummary),
				"income_expense_trend" => $incomeExpenseTrend,
				"category_analysis" => $categoryAnalysis,
				"budget_analysis" => $budgetAnalysis,
				"account_analysis" => $accountAnalysis,
				"period" => $financialSummary["period"],
			];
		});
	}

	public function getCustomReport(
		int $userId,
		string $reportType,
		array $params = []
	): array {
		$params["user_id"] = $userId;

		switch ($reportType) {
			case "financial_summary":
				$data = $this->reportRepository->getFinancialSummary($params);
				return $this->formatMoneyValues($data);

			case "income_expense_trend":
				return $this->reportRepository->getIncomeExpenseTrend($params);

			case "category_analysis":
				return $this->reportRepository->getCategoryAnalysis($params);

			case "budget_analysis":
				$data = $this->reportRepository->getBudgetAnalysis($params);
				return $this->formatMoneyValues($data, ["summary"]);

			case "account_analysis":
				$data = $this->reportRepository->getAccountAnalysis($params);
				return $this->formatMoneyValues($data, ["summary"]);

			case "transaction_analysis":
				return $this->reportRepository->getTransactionAnalysis($params);

			default:
				throw new \InvalidArgumentException(
					"Report type {$reportType} not supported"
				);
		}
	}

	public function getMonthlyReport(int $userId, int $year, int $month): array
	{
		$cacheKey = "monthly_report_{$userId}_{$year}_{$month}";

		return Cache::remember($cacheKey, 3600, function () use (
			$userId,
			$year,
			$month
		) {
			$startDate = Carbon::create($year, $month, 1)->startOfMonth();
			$endDate = Carbon::create($year, $month, 1)->endOfMonth();

			$params = [
				"user_id" => $userId,
				"start_date" => $startDate,
				"end_date" => $endDate,
				"period" => "monthly",
			];

			return [
				"monthly_summary" => $this->reportRepository->getFinancialSummary(
					$params
				),
				"daily_trend" => $this->reportRepository->getIncomeExpenseTrend(
					array_merge($params, ["group_by" => "day"])
				),
				"category_breakdown" => [
					"income" => $this->reportRepository->getCategoryAnalysis(
						array_merge($params, ["type" => "income"])
					),
					"expense" => $this->reportRepository->getCategoryAnalysis(
						array_merge($params, ["type" => "expense"])
					),
				],
				"budget_status" => $this->reportRepository->getBudgetAnalysis($params),
				"month" => $month,
				"year" => $year,
			];
		});
	}

	public function getYearlyReport(int $userId, int $year): array
	{
		$cacheKey = "yearly_report_{$userId}_{$year}";

		return Cache::remember($cacheKey, 3600, function () use ($userId, $year) {
			$startDate = Carbon::create($year, 1, 1)->startOfYear();
			$endDate = Carbon::create($year, 12, 31)->endOfYear();

			$params = [
				"user_id" => $userId,
				"start_date" => $startDate,
				"end_date" => $endDate,
			];

			return [
				"yearly_summary" => $this->reportRepository->getFinancialSummary(
					$params
				),
				"monthly_trend" => $this->reportRepository->getIncomeExpenseTrend(
					array_merge($params, ["group_by" => "month"])
				),
				"top_categories" => [
					"income" => $this->reportRepository->getCategoryAnalysis(
						array_merge($params, ["type" => "income", "limit" => 5])
					),
					"expense" => $this->reportRepository->getCategoryAnalysis(
						array_merge($params, ["type" => "expense", "limit" => 5])
					),
				],
				"account_distribution" => $this->reportRepository->getAccountAnalysis(
					$params
				),
				"year" => $year,
			];
		});
	}

	public function getExportData(int $userId, array $params): array
	{
		$data = $this->getDashboardSummary($userId, $params);

		return [
			"report_data" => $data,
			"exported_at" => now()->toDateTimeString(),
			"period" => $data["period"],
			"currency" => "IDR",
		];
	}

	private function formatMoneyValues(
		array $data,
		array $keysToFormat = []
	): array {
		foreach ($data as $key => $value) {
			if (is_array($value)) {
				$data[$key] = $this->formatMoneyValues($value, $keysToFormat);
			} elseif (
				is_int($value) &&
				(empty($keysToFormat) || in_array($key, $keysToFormat))
			) {
				if (
					strpos($key, "total_") !== false ||
					strpos($key, "_amount") !== false
				) {
					try {
						$data[$key] = Money::ofMinor($value, "IDR")->formatTo("id_ID");
					} catch (\Exception $e) {
						// Keep original value if formatting fails
					}
				}
			}
		}

		return $data;
	}
}
