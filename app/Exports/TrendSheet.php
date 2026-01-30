<?php

namespace Modules\Wallet\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TrendSheet implements FromArray, WithTitle, WithHeadings
{
	protected $reportData;

	public function __construct(array $reportData)
	{
		$this->reportData = $reportData;
	}

	public function array(): array
	{
		$trend = $this->reportData["report_data"]["income_expense_trend"] ?? [];
		$labels = $trend["labels"] ?? [];
		$incomeData = $trend["datasets"][0]["data"] ?? [];
		$expenseData = $trend["datasets"][1]["data"] ?? [];

		$data = [];
		foreach ($labels as $index => $label) {
			$data[] = [
				$label,
				$this->formatCurrency($incomeData[$index] ?? 0),
				$this->formatCurrency($expenseData[$index] ?? 0),
				$this->formatCurrency(
					($incomeData[$index] ?? 0) - ($expenseData[$index] ?? 0)
				),
			];
		}

		// Add totals row
		$data[] = [
			"TOTAL",
			$this->formatCurrency(array_sum($incomeData)),
			$this->formatCurrency(array_sum($expenseData)),
			$this->formatCurrency(array_sum($incomeData) - array_sum($expenseData)),
		];

		return $data;
	}

	public function headings(): array
	{
		return ["Periode", "Pendapatan", "Pengeluaran", "Saldo Bersih"];
	}

	public function title(): string
	{
		return "Trend";
	}

	private function formatCurrency($value)
	{
		$amount = is_numeric($value) ? $value / 100 : 0;
		return "Rp " . number_format($amount, 0, ",", ".");
	}
}
