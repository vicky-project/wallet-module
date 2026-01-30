<?php

namespace Modules\Wallet\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BudgetSheet implements FromArray, WithTitle, WithHeadings, WithStyles
{
	protected $reportData;

	public function __construct(array $reportData)
	{
		$this->reportData = $reportData;
	}

	public function array(): array
	{
		$budgetData = $this->reportData["report_data"]["budget_analysis"] ?? [
			"labels" => [],
			"datasets" => [["data" => []], ["data" => []]],
		];

		$labels = $budgetData["labels"] ?? [];
		$budgetValues = $budgetData["datasets"][0]["data"] ?? [];
		$spentValues = $budgetData["datasets"][1]["data"] ?? [];

		$data = [
			["ANALISIS ANGGARAN VS REALISASI"],
			[""],
			["Anggaran", "Direncanakan", "Terpakai", "Sisa", "Penggunaan", "Status"],
		];

		foreach ($labels as $index => $label) {
			$budget = $budgetValues[$index] ?? 0;
			$spent = $spentValues[$index] ?? 0;
			$remaining = max(0, $budget - $spent);
			$usagePercentage = $budget > 0 ? ($spent / $budget) * 100 : 0;

			$data[] = [
				$label,
				$this->formatCurrency($budget),
				$this->formatCurrency($spent),
				$this->formatCurrency($remaining),
				number_format($usagePercentage, 2) . "%",
				$this->getUsageStatus($usagePercentage),
			];
		}

		// Add summary row
		$totalBudget = array_sum($budgetValues);
		$totalSpent = array_sum($spentValues);
		$totalRemaining = $totalBudget - $totalSpent;
		$totalPercentage =
			$totalBudget > 0 ? ($totalSpent / $totalBudget) * 100 : 0;

		if (count($labels) > 0) {
			$data[] = [
				"TOTAL",
				$this->formatCurrency($totalBudget),
				$this->formatCurrency($totalSpent),
				$this->formatCurrency($totalRemaining),
				number_format($totalPercentage, 2) . "%",
				$this->getUsageStatus($totalPercentage),
			];
		} else {
			$data[] = ["Tidak ada data anggaran"];
		}

		return $data;
	}

	public function headings(): array
	{
		return [];
	}

	public function title(): string
	{
		return "Anggaran";
	}

	public function styles(Worksheet $sheet)
	{
		// Set column widths
		$sheet->getColumnDimension("A")->setWidth(25);
		$sheet->getColumnDimension("B")->setWidth(20);
		$sheet->getColumnDimension("C")->setWidth(20);
		$sheet->getColumnDimension("D")->setWidth(20);
		$sheet->getColumnDimension("E")->setWidth(15);
		$sheet->getColumnDimension("F")->setWidth(15);

		// Style judul
		$sheet
			->getStyle("A1")
			->getFont()
			->setBold(true)
			->setSize(14);
		$sheet->mergeCells("A1:F1");

		// Style header tabel
		$sheet
			->getStyle("A3:F3")
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
			->getStyle("A3:F" . $lastRow)
			->getBorders()
			->getAllBorders()
			->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

		// Alignment
		$sheet
			->getStyle("A3:F" . $lastRow)
			->getAlignment()
			->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
		$sheet
			->getStyle("B4:D" . $lastRow)
			->getAlignment()
			->setHorizontal(
				\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT
			);
		$sheet
			->getStyle("E4:F" . $lastRow)
			->getAlignment()
			->setHorizontal(
				\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
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

	private function getUsageStatus($percentage)
	{
		if ($percentage < 70) {
			return "Baik";
		} elseif ($percentage < 90) {
			return "Hati-hati";
		} else {
			return "Melebihi";
		}
	}
}
