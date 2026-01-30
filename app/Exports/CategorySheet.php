<?php

namespace Modules\Wallet\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CategorySheet implements FromArray, WithTitle, WithHeadings, WithStyles
{
	protected $reportData;

	public function __construct(array $reportData)
	{
		$this->reportData = $reportData;
	}

	public function array(): array
	{
		$categoryData = $this->reportData["report_data"]["category_analysis"] ?? [
			"labels" => [],
			"datasets" => [["data" => []]],
		];

		$labels = $categoryData["labels"] ?? [];
		$values = $categoryData["datasets"][0]["data"] ?? [];
		$total = array_sum($values);

		$data = [
			["ANALISIS PENGELUARAN PER KATEGORI"],
			[""],
			["Kategori", "Jumlah", "Persentase", "Tipe"],
		];

		// Data rows
		foreach ($labels as $index => $label) {
			$amount = $values[$index] ?? 0;
			$percentage = $total > 0 ? ($amount / $total) * 100 : 0;

			$data[] = [
				$label,
				$this->formatCurrency($amount),
				number_format($percentage, 2) . "%",
				$this->getCategoryType($label),
			];
		}

		// Total row
		if (count($labels) > 0) {
			$data[] = ["TOTAL", $this->formatCurrency($total), "100%", ""];
		} else {
			$data[] = ["Tidak ada data kategori"];
		}

		return $data;
	}

	public function headings(): array
	{
		return [];
	}

	public function title(): string
	{
		return "Kategori";
	}

	public function styles(Worksheet $sheet)
	{
		// Set column widths
		$sheet->getColumnDimension("A")->setWidth(30);
		$sheet->getColumnDimension("B")->setWidth(20);
		$sheet->getColumnDimension("C")->setWidth(15);
		$sheet->getColumnDimension("D")->setWidth(15);

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

		// Format angka untuk kolom B
		$lastRow = count($this->array());
		for ($row = 4; $row <= $lastRow; $row++) {
			$sheet
				->getStyle("B{$row}")
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
			->getStyle("B4:B" . $lastRow)
			->getAlignment()
			->setHorizontal(
				\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT
			);
		$sheet
			->getStyle("C4:C" . $lastRow)
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

	private function getCategoryType($label)
	{
		$expenseKeywords = ["makan", "transport", "belanja", "hiburan", "tagihan"];
		$incomeKeywords = ["gaji", "bonus", "investasi", "penjualan"];

		$labelLower = strtolower($label);

		foreach ($expenseKeywords as $keyword) {
			if (strpos($labelLower, $keyword) !== false) {
				return "Pengeluaran";
			}
		}

		foreach ($incomeKeywords as $keyword) {
			if (strpos($labelLower, $keyword) !== false) {
				return "Pendapatan";
			}
		}

		return "Lainnya";
	}
}
