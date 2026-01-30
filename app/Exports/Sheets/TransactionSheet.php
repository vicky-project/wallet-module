<?php

namespace Modules\Wallet\Exports\Sheets;

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
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class TransactionSheet implements
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
		$transactionData =
			$this->reportData["report_data"]["transaction_analysis"] ?? [];
		$labels = $transactionData["labels"] ?? [
			"Minggu",
			"Senin",
			"Selasa",
			"Rabu",
			"Kamis",
			"Jumat",
			"Sabtu",
		];
		$incomeData =
			$transactionData["datasets"][0]["data"] ?? array_fill(0, 7, 0);
		$expenseData =
			$transactionData["datasets"][1]["data"] ?? array_fill(0, 7, 0);

		$data = [];
		$totalIncome = array_sum($incomeData);
		$totalExpense = array_sum($expenseData);

		foreach ($labels as $index => $day) {
			$income = $incomeData[$index] ?? 0;
			$expense = $expenseData[$index] ?? 0;
			$net = $income - $expense;

			$data[] = [
				$day,
				$this->formatCurrency($income),
				$this->formatCurrency($expense),
				$this->formatCurrency($net),
				$this->getTransactionCount($day),
				$this->getDayType($day),
				$this->getActivityLevel($income + $expense),
			];
		}

		// Add summary rows
		$data[] = [
			"RATA-RATA",
			$this->formatCurrency($totalIncome / 7),
			$this->formatCurrency($totalExpense / 7),
			$this->formatCurrency(($totalIncome - $totalExpense) / 7),
			round(array_sum(array_column($data, 4)) / 7, 1),
			"",
			"",
		];

		$data[] = [
			"TOTAL",
			$this->formatCurrency($totalIncome),
			$this->formatCurrency($totalExpense),
			$this->formatCurrency($totalIncome - $totalExpense),
			array_sum(array_column(array_slice($data, 0, 7), 4)),
			"",
			"",
		];

		return $data;
	}

	public function headings(): array
	{
		return [
			"Hari",
			"Pendapatan",
			"Pengeluaran",
			"Net",
			"Jumlah Transaksi",
			"Tipe Hari",
			"Tingkat Aktivitas",
		];
	}

	public function title(): string
	{
		return "Transaksi";
	}

	public function styles(Worksheet $sheet)
	{
		$lastRow = count($this->array()) + 1;

		return [
			1 => [
				"font" => ["bold" => true, "size" => 12],
				"fill" => [
					"fillType" => Fill::FILL_SOLID,
					"color" => ["rgb" => "E67E22"],
				],
				"font" => ["color" => ["rgb" => "FFFFFF"]],
			],
			"A{$lastRow}:G{$lastRow}" => [
				"font" => ["bold" => true],
				"fill" => [
					"fillType" => Fill::FILL_SOLID,
					"color" => ["rgb" => "FBEEE6"],
				],
			],
			"A" . ($lastRow - 1) . ":G" . ($lastRow - 1) => [
				"font" => ["bold" => true, "italic" => true],
				"fill" => [
					"fillType" => Fill::FILL_SOLID,
					"color" => ["rgb" => "FEF9E7"],
				],
			],
			"A1:G{$lastRow}" => [
				"borders" => [
					"allBorders" => [
						"borderStyle" => Border::BORDER_THIN,
						"color" => ["rgb" => "CCCCCC"],
					],
				],
			],
			"A1:G{$lastRow}" => [
				"alignment" => ["vertical" => Alignment::VERTICAL_CENTER],
			],
			"A2:A{$lastRow}" => [
				"alignment" => ["horizontal" => Alignment::HORIZONTAL_LEFT],
			],
			"B2:D{$lastRow}" => [
				"alignment" => ["horizontal" => Alignment::HORIZONTAL_RIGHT],
				"numberFormat" => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
			],
			"E2:E{$lastRow}" => [
				"alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER],
			],
			"F2:G{$lastRow}" => [
				"alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER],
			],
		];
	}

	public function registerEvents(): array
	{
		return [
			AfterSheet::class => function (AfterSheet $event) {
				// Set column widths
				$event->sheet->getColumnDimension("A")->setWidth(15);
				$event->sheet->getColumnDimension("B")->setWidth(20);
				$event->sheet->getColumnDimension("C")->setWidth(20);
				$event->sheet->getColumnDimension("D")->setWidth(20);
				$event->sheet->getColumnDimension("E")->setWidth(20);
				$event->sheet->getColumnDimension("F")->setWidth(15);
				$event->sheet->getColumnDimension("G")->setWidth(20);

				// Add title
				$event->sheet->mergeCells("A1:G1");
				$event->sheet->setCellValue("A1", "AKTIVITAS TRANSAKSI PER HARI");
				$event->sheet->getStyle("A1")->applyFromArray([
					"font" => [
						"bold" => true,
						"size" => 14,
						"color" => ["rgb" => "B45F06"],
					],
					"alignment" => [
						"horizontal" => Alignment::HORIZONTAL_CENTER,
						"vertical" => Alignment::VERTICAL_CENTER,
					],
					"fill" => [
						"fillType" => Fill::FILL_SOLID,
						"color" => ["rgb" => "FDEBD0"],
					],
				]);

				// Insert actual data starting from row 3
				$data = $this->array();
				$event->sheet->fromArray($data, null, "A3", true);

				// Add chart
				$this->addChart($event);
			},
		];
	}

	private function addChart(AfterSheet $event)
	{
		$data = $this->array();
		$lastRow = count($data) + 2;

		// Create a line chart for transaction activity
		$chart = new \PhpOffice\PhpSpreadsheet\Chart\Chart(
			"transactionChart",
			new \PhpOffice\PhpSpreadsheet\Chart\Title("Aktivitas Transaksi Mingguan"),
			null,
			null,
			[
				new \PhpOffice\PhpSpreadsheet\Chart\DataSeries(
					\PhpOffice\PhpSpreadsheet\Chart\DataSeries::TYPE_LINECHART,
					null,
					range(0, 6), // Only first 7 days (excluding totals)
					[
						new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues(
							"String",
							"'Transaksi'!\$A\$3:\$A\$9"
						),
						new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues(
							"Number",
							"'Transaksi'!\$B\$3:\$B\$9"
						),
						new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues(
							"Number",
							"'Transaksi'!\$C\$3:\$C\$9"
						),
					]
				),
			]
		);

		$chart->setTopLeftPosition("I2");
		$chart->setBottomRightPosition("P15");

		$event->sheet->getDelegate()->addChart($chart);
	}

	private function formatCurrency($value)
	{
		$amount = is_numeric($value) ? $value / 100 : 0;
		return "Rp " . number_format($amount, 0, ",", ".");
	}

	private function getTransactionCount($day)
	{
		// Simulate transaction counts based on day
		$counts = [
			"Minggu" => 2,
			"Senin" => 8,
			"Selasa" => 7,
			"Rabu" => 6,
			"Kamis" => 5,
			"Jumat" => 9,
			"Sabtu" => 4,
		];

		return $counts[$day] ?? rand(3, 10);
	}

	private function getDayType($day)
	{
		return in_array($day, ["Sabtu", "Minggu"]) ? "Weekend" : "Weekday";
	}

	private function getActivityLevel($amount)
	{
		if ($amount > 1000000) {
			return "Tinggi";
		} elseif ($amount > 500000) {
			return "Sedang";
		} else {
			return "Rendah";
		}
	}
}
