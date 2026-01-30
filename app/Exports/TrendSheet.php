<?php

namespace Modules\Wallet\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TrendSheet implements FromArray, WithTitle, WithHeadings, WithStyles
{
	protected $reportData;

	public function __construct(array $reportData)
	{
		$this->reportData = $reportData;
	}

	public function array(): array
	{
		$trendData = $this->reportData["report_data"]["income_expense_trend"] ?? [
			"labels" => [],
			"datasets" => [["data" => []], ["data" => []]],
		];

		$labels = $trendData["labels"] ?? [];
		$incomeData = $trendData["datasets"][0]["data"] ?? [];
		$expenseData = $trendData["datasets"][1]["data"] ?? [];

		$data = [
			["TREND PENDAPATAN VS PENGELUARAN"],
			[""],
			["Periode", "Pendapatan", "Pengeluaran", "Saldo Bersih"],
		];

		foreach ($labels as $index => $label) {
			$income = $incomeData[$index] ?? 0;
			$expense = $expenseData[$index] ?? 0;

			$data[] = [
				$label,
				$this->formatCurrency($income),
				$this->formatCurrency($expense),
				$this->formatCurrency($income - $expense),
			];
		}

		if (count($labels) > 0) {
			$totalIncome = array_sum($incomeData);
			$totalExpense = array_sum($expenseData);

			$data[] = [
				"TOTAL",
				$this->formatCurrency($totalIncome),
				$this->formatCurrency($totalExpense),
				$this->formatCurrency($totalIncome - $totalExpense),
			];
		} else {
			$data[] = ["Tidak ada data trend"];
		}

		return $data;
	}

	public function headings(): array
	{
		return [];
	}

	public function title(): string
	{
		return "Trend";
	}

	public function styles(Worksheet $sheet)
	{
		// Set column widths
		$sheet->getColumnDimension("A")->setWidth(20);
		$sheet->getColumnDimension("B")->setWidth(20);
		$sheet->getColumnDimension("C")->setWidth(20);
		$sheet->getColumnDimension("D")->setWidth(20);

		// Style judul
		$sheet
			->getStyle("A1")
			->getFont()
			->setBold(true)
			->setSize(14);
		$sheet->mergeCells("A1:D1");

		// Style header tabel
		$sheet
			->getStyle("A3:D3")
			->getFont()
			->setBold(true);

		// Format angka untuk kolom B, C, D
		$lastRow = count($this->array());
		for ($row = 4; $row <= $lastRow; $row++) {
			$sheet
				->getStyle("B{$row}")
				->getNumberFormat()
				->setFormatCode("#,##0");
			$sheet
				->getStyle("C{$row}")
				->getNumberFormat()
				->setFormatCode("#,##0");
			$sheet
				->getStyle("D{$row}")
				->getNumberFormat()
				->setFormatCode("#,##0");
		}

		// Border
		$sheet
			->getStyle("A3:D" . $lastRow)
			->getBorders()
			->getAllBorders()
			->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

		// Alignment
		$sheet
			->getStyle("A3:D" . $lastRow)
			->getAlignment()
			->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
		$sheet
			->getStyle("B4:D" . $lastRow)
			->getAlignment()
			->setHorizontal(
				\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT
			);
	}

	private function formatCurrency($value)
	{
		if (!is_numeric($value)) {
			return 0;
		}
		$amount = $value / 100;
		return $amount;
	}
}
