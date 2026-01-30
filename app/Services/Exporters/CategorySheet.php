<?php

namespace Modules\Wallet\Services\Exporters;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CategorySheet implements
	FromArray,
	WithTitle,
	WithHeadings,
	WithStyles,
	WithEvents
{
	protected $reportData;

	public function __construct(array $reportData)
	{
		$this->reportData = $reportData;
	}

	public function array(): array
	{
		$categoryData = $this->reportData["report_data"]["category_analysis"] ?? [];
		$labels = $categoryData["labels"] ?? [];
		$values = $categoryData["datasets"][0]["data"] ?? [];
		$colors = $categoryData["datasets"][0]["backgroundColor"] ?? [];

		$data = [];
		$total = array_sum($values);

		foreach ($labels as $index => $label) {
			$amount = $values[$index] ?? 0;
			$percentage = $total > 0 ? ($amount / $total) * 100 : 0;

			$data[] = [
				$label,
				$this->formatCurrency($amount),
				number_format($percentage, 2) . "%",
				$this->getCategoryType($label),
				$colors[$index] ?? "#cccccc",
			];
		}

		// Sort by amount descending
		usort($data, function ($a, $b) {
			$amountA = floatval(str_replace(["Rp", ".", ","], "", $a[1]));
			$amountB = floatval(str_replace(["Rp", ".", ","], "", $b[1]));
			return $amountB <=> $amountA;
		});

		// Add totals row
		$data[] = ["TOTAL", $this->formatCurrency($total), "100%", "", ""];

		return $data;
	}

	public function headings(): array
	{
		return ["Kategori", "Jumlah", "Persentase", "Tipe", "Warna"];
	}

	public function title(): string
	{
		return "Kategori";
	}

	public function styles(Worksheet $sheet)
	{
		$lastRow = count($this->array()) + 1;

		return [
			1 => [
				"font" => ["bold" => true, "size" => 12],
				"fill" => [
					"fillType" => Fill::FILL_SOLID,
					"color" => ["rgb" => "2E86C1"],
				],
				"font" => ["color" => ["rgb" => "FFFFFF"]],
			],
			"A{$lastRow}:D{$lastRow}" => [
				"font" => ["bold" => true],
				"fill" => [
					"fillType" => Fill::FILL_SOLID,
					"color" => ["rgb" => "D6EAF8"],
				],
			],
			"A1:E{$lastRow}" => [
				"borders" => [
					"allBorders" => [
						"borderStyle" => Border::BORDER_THIN,
						"color" => ["rgb" => "CCCCCC"],
					],
				],
			],
			"A1:E{$lastRow}" => [
				"alignment" => ["vertical" => Alignment::VERTICAL_CENTER],
			],
			"A2:A{$lastRow}" => [
				"alignment" => ["horizontal" => Alignment::HORIZONTAL_LEFT],
			],
			"B2:B{$lastRow}" => [
				"alignment" => ["horizontal" => Alignment::HORIZONTAL_RIGHT],
			],
			"C2:C{$lastRow}" => [
				"alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER],
			],
		];
	}

	public function registerEvents(): array
	{
		return [
			AfterSheet::class => function (AfterSheet $event) {
				// Set column widths
				$event->sheet->getColumnDimension("A")->setWidth(30);
				$event->sheet->getColumnDimension("B")->setWidth(20);
				$event->sheet->getColumnDimension("C")->setWidth(15);
				$event->sheet->getColumnDimension("D")->setWidth(15);
				$event->sheet->getColumnDimension("E")->setWidth(10);

				// Hide color column
				$event->sheet->getColumnDimension("E")->setVisible(false);

				// Add title
				$event->sheet->mergeCells("A1:E1");
				$event->sheet->setCellValue("A1", "ANALISIS PENGELUARAN PER KATEGORI");
				$event->sheet->getStyle("A1")->applyFromArray([
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

				// Insert actual data starting from row 3
				$data = $this->array();
				$event->sheet->fromArray($this->array(), null, "A3", true);

				// Add chart (Excel chart)
				$this->addChart($event);
			},
		];
	}

	private function addChart(AfterSheet $event)
	{
		$data = $this->array();
		$lastRow = count($data) + 2; // +2 because we start from row 3 and include header

		// Create a pie chart
		$chart = new \PhpOffice\PhpSpreadsheet\Chart\Chart(
			"categoryChart",
			new \PhpOffice\PhpSpreadsheet\Chart\Title(
				"Distribusi Pengeluaran per Kategori"
			),
			null,
			null,
			[
				new \PhpOffice\PhpSpreadsheet\Chart\DataSeries(
					\PhpOffice\PhpSpreadsheet\Chart\DataSeries::TYPE_PIECHART,
					null,
					range(0, count($data) - 2), // Exclude total row
					[
						new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues(
							"String",
							"'Kategori'!\$A\$3:\$A\$" . ($lastRow - 1)
						),
						new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues(
							"Number",
							"'Kategori'!\$B\$3:\$B\$" . ($lastRow - 1)
						),
					]
				),
			]
		);

		$chart->setTopLeftPosition("G2");
		$chart->setBottomRightPosition("M15");

		$event->sheet->getDelegate()->addChart($chart);
	}

	private function formatCurrency($value)
	{
		$amount = is_numeric($value) ? $value / 100 : 0;
		return "Rp " . number_format($amount, 0, ",", ".");
	}

	private function getCategoryType($label)
	{
		// Logic to determine category type
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
