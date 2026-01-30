<?php

namespace Modules\Wallet\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
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

	// Di method registerEvents() pada CategorySheet
	public function registerEvents(): array
	{
		return [
			AfterSheet::class => function (AfterSheet $event) {
				$sheet = $event->sheet->getDelegate();

				// Set column widths
				$sheet->getColumnDimension("A")->setWidth(30);
				$sheet->getColumnDimension("B")->setWidth(20);
				$sheet->getColumnDimension("C")->setWidth(15);
				$sheet->getColumnDimension("D")->setWidth(15);
				$sheet->getColumnDimension("E")->setWidth(10);

				// Hide color column
				$sheet->getColumnDimension("E")->setVisible(false);

				// Get the actual data (excluding header row)
				$data = $this->array();
				$lastDataRow = count($data) + 1; // +1 for header

				// Add title
				$sheet->insertNewRowBefore(1, 2);
				$sheet->mergeCells("A1:E1");
				$sheet->setCellValue("A1", "ANALISIS PENGELUARAN PER KATEGORI");
				$sheet->getStyle("A1")->applyFromArray([
					"font" => [
						"bold" => true,
						"size" => 14,
						"color" => ["rgb" => "2C3E50"],
					],
					"alignment" => [
						"horizontal" => Alignment::HORIZONTAL_CENTER,
						"vertical" => Alignment::VERTICAL_CENTER,
					],
					"fill" => [
						"fillType" => Fill::FILL_SOLID,
						"color" => ["rgb" => "F4F6F6"],
					],
				]);

				// Add chart if data exists
				$this->addChartSafely($event);

				// Adjust row numbers for data (since we inserted 2 rows)
				$newLastRow = $lastDataRow + 2;

				// Apply styles to the new range
				$sheet->getStyle("A3:E{$newLastRow}")->applyFromArray([
					"borders" => [
						"allBorders" => [
							"borderStyle" => Border::BORDER_THIN,
							"color" => ["rgb" => "CCCCCC"],
						],
					],
					"alignment" => ["vertical" => Alignment::VERTICAL_CENTER],
				]);

				// Style for total row
				$sheet->getStyle("A{$newLastRow}:D{$newLastRow}")->applyFromArray([
					"font" => ["bold" => true],
					"fill" => [
						"fillType" => Fill::FILL_SOLID,
						"color" => ["rgb" => "D6EAF8"],
					],
				]);
			},
		];
	}

	private function addChartSafely(AfterSheet $event)
	{
		try {
			$categoryData =
				$this->reportData["report_data"]["category_analysis"] ?? [];
			$labels = $categoryData["labels"] ?? [];
			$values = $categoryData["datasets"][0]["data"] ?? [];

			// Cek apakah ada data yang cukup untuk chart
			if (empty($labels) || empty($values) || count($labels) < 1) {
				return;
			}

			// Limit data untuk chart (max 10 items)
			if (count($labels) > 10) {
				$labels = array_slice($labels, 0, 10);
				$values = array_slice($values, 0, 10);
			}

			// Create chart
			$chart = ChartService::createPieChart(
				$labels,
				$values,
				"Distribusi Pengeluaran per Kategori"
			);

			if ($chart) {
				$chart->setTopLeftPosition("F2");
				$chart->setBottomRightPosition("M20");
				$event->sheet->getDelegate()->addChart($chart);
			}
		} catch (\Exception $e) {
			\Log::warning("Chart creation failed: " . $e->getMessage());
			// Continue without chart
		}
	}
}
