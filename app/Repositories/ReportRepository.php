<?php

namespace Modules\Wallet\Repositories;

use App\Models\User;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportRepository
{
	protected $transactionRepository;

	public function __construct(TransactionRepository $transactionRepository)
	{
		$this->transactionRepository = $transactionRepository;
	}

	/**
	 * Get monthly summary for chart
	 */
	public function getMonthlySummary(User $user, int $months = 6): array
	{
		$data = [
			"labels" => [],
			"income" => [],
			"expense" => [],
			"net" => [],
		];

		$today = Carbon::now();

		for ($i = $months - 1; $i >= 0; $i--) {
			$date = $today->copy()->subMonths($i);
			$month = $date->month;
			$year = $date->year;

			$summary = $this->transactionRepository->getSummary($user, $month, $year);

			$data["labels"][] = $date->format("M Y");
			$data["income"][] = $summary["income"]->getMinorAmount()->toInt();
			$data["expense"][] = $summary["expense"]->getMinorAmount()->toInt();
			$data["net"][] = $summary["net_balance"]->getMinorAmount()->toInt();
		}

		return $data;
	}

	/**
	 * Get expense by category
	 */
	public function getExpenseByCategory(
		User $user,
		int $month = null,
		int $year = null
	): Collection {
		$month = $month ?? Carbon::now()->month;
		$year = $year ?? Carbon::now()->year;

		// This would typically be a more optimized query
		// For now, we'll get all expense transactions and group them
		$transactions = $this->transactionRepository->model
			->where("user_id", $user->id)
			->where("type", "expense")
			->whereMonth("transaction_date", $month)
			->whereYear("transaction_date", $year)
			->with("category")
			->get();

		return $transactions
			->groupBy("category_id")
			->map(function ($group, $categoryId) {
				$category = $group->first()->category;
				$total = $group->sum("amount");

				return [
					"category_id" => $categoryId,
					"category_name" => $category->name,
					"category_color" => $category->color,
					"category_icon" => $category->icon,
					"total_amount" => Money::ofMinor($total, "IDR"),
					"formatted_total" => Money::ofMinor($total, "IDR")->formatTo("id_ID"),
					"transaction_count" => $group->count(),
					"percentage" => 0, // Will be calculated after we have grand total
				];
			})
			->values();
	}

	/**
	 * Get income vs expense comparison
	 */
	public function getIncomeExpenseComparison(
		User $user,
		string $period = "monthly"
	): array {
		$today = Carbon::now();
		$data = [];

		switch ($period) {
			case "weekly":
				$startDate = $today->copy()->startOfWeek();
				$endDate = $today->copy()->endOfWeek();
				$label = "Minggu Ini";
				break;

			case "monthly":
				$startDate = $today->copy()->startOfMonth();
				$endDate = $today->copy()->endOfMonth();
				$label = "Bulan Ini";
				break;

			case "yearly":
				$startDate = $today->copy()->startOfYear();
				$endDate = $today->copy()->endOfYear();
				$label = "Tahun Ini";
				break;

			default:
				$startDate = $today->copy()->startOfMonth();
				$endDate = $today->copy()->endOfMonth();
				$label = "Bulan Ini";
		}

		$income = $this->transactionRepository->model
			->where("user_id", $user->id)
			->where("type", "income")
			->whereBetween("transaction_date", [$startDate, $endDate])
			->sum("amount");

		$expense = $this->transactionRepository->model
			->where("user_id", $user->id)
			->where("type", "expense")
			->whereBetween("transaction_date", [$startDate, $endDate])
			->sum("amount");

		$incomeMoney = Money::ofMinor($income, "IDR");
		$expenseMoney = Money::ofMinor($expense, "IDR");
		$netMoney = $incomeMoney->minus($expenseMoney);

		return [
			"period" => $period,
			"label" => $label,
			"income" => [
				"amount" => $incomeMoney,
				"formatted" => $incomeMoney->formatTo("id_ID"),
			],
			"expense" => [
				"amount" => $expenseMoney,
				"formatted" => $expenseMoney->formatTo("id_ID"),
			],
			"net" => [
				"amount" => $netMoney,
				"formatted" => $netMoney->formatTo("id_ID"),
				"is_positive" => !$netMoney->isNegative(),
			],
			"savings_rate" => $incomeMoney->isZero()
				? 0
				: ($netMoney->getAmount()->toFloat() /
						$incomeMoney->getAmount()->toFloat()) *
					100,
		];
	}

	/**
	 * Get cash flow data
	 */
	public function getCashFlowData(User $user, int $months = 12): array
	{
		$data = [
			"labels" => [],
			"income" => [],
			"expense" => [],
			"cash_flow" => [],
		];

		$today = Carbon::now();

		for ($i = $months - 1; $i >= 0; $i--) {
			$date = $today->copy()->subMonths($i);
			$month = $date->month;
			$year = $date->year;

			$summary = $this->transactionRepository->getSummary($user, $month, $year);

			$data["labels"][] = $date->format("M Y");
			$data["income"][] = $summary["income"]->getMinorAmount()->toInt();
			$data["expense"][] = $summary["expense"]->getMinorAmount()->toInt();
			$data["cash_flow"][] = $summary["net_balance"]->getMinorAmount()->toInt();
		}

		return $data;
	}
}
