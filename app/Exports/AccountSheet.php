<?php

namespace Modules\Wallet\Exports;

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

class AccountSheet implements
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
		$accountData = $this->reportData["report_data"]["account_analysis"] ?? [];
		$labels = $accountData["labels"] ?? [];
		$balances = $accountData["datasets"][0]["data"] ?? [];
		$colors = $accountData["datasets"][0]["backgroundColor"] ?? [];

		$data = [];
		$totalBalance = array_sum($balances);

		foreach ($labels as $index => $label) {
			$balance = $balances[$index] ?? 0;
			$percentage = $totalBalance > 0 ? ($balance / $totalBalance) * 100 : 0;

			$data[] = [
				$label,
				$this->formatCurrency($balance),
				number_format($percentage, 2) . "%",
				$this->getAccountType($label),
				$this->formatCurrency($this->getAccountGrowth($label, $balance)),
				$colors[$index] ?? "#cccccc",
				$this->getHealthIndicator($balance),
			];
		}

		// Sort by balance descending
		usort($data, function ($a, $b) {
			$balanceA = floatval(str_replace(["Rp", ".", ","], "", $a[1]));
			$balanceB = floatval(str_replace(["Rp", ".", ","], "", $b[1]));
			return $balanceB <=> $balanceA;
		});

		// Add totals row
		$data[] = [
			"TOTAL",
			$this->formatCurrency($totalBalance),
			"100%",
			"",
			"",
			"",
			"✓",
		];

		return $data;
	}

	public function headings(): array
	{
		return [
			"Akun",
			"Saldo",
			"Persentase",
			"Tipe",
			"Pertumbuhan",
			"Warna",
			"Status",
		];
	}

	public function title(): string
	{
		return "Akun";
	}

	public function styles(Worksheet $sheet)
	{
		$lastRow = count($this->array()) + 1;

		return [
			1 => [
				"font" => ["bold" => true, "size" => 12],
				"fill" => [
					"fillType" => Fill::FILL_SOLID,
					"color" => ["rgb" => "8E44AD"],
				],
				"font" => ["color" => ["rgb" => "FFFFFF"]],
			],
			"A{$lastRow}:G{$lastRow}" => [
				"font" => ["bold" => true],
				"fill" => [
					"fillType" => Fill::FILL_SOLID,
					"color" => ["rgb" => "F4ECF7"],
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
			"B2:B{$lastRow}" => [
				"alignment" => ["horizontal" => Alignment::HORIZONTAL_RIGHT],
				"numberFormat" => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
			],
			"C2:C{$lastRow}" => [
				"alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER],
			],
			"D2:D{$lastRow}" => [
				"alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER],
			],
			"E2:E{$lastRow}" => [
				"alignment" => ["horizontal" => Alignment::HORIZONTAL_RIGHT],
			],
		];
	}

	public function registerEvents(): array
	{
		return [
			AfterSheet::class => function (AfterSheet $event) {
				// Set column widths
				$event->sheet->getColumnDimension("A")->setWidth(25);
				$event->sheet->getColumnDimension("B")->setWidth(20);
				$event->sheet->getColumnDimension("C")->setWidth(15);
				$event->sheet->getColumnDimension("D")->setWidth(15);
				$event->sheet->getColumnDimension("E")->setWidth(20);
				$event->sheet->getColumnDimension("F")->setWidth(10);
				$event->sheet->getColumnDimension("G")->setWidth(10);

				// Hide color and status columns
				$event->sheet->getColumnDimension("F")->setVisible(false);
				$event->sheet->getColumnDimension("G")->setVisible(false);

				// Add title
				$event->sheet->mergeCells("A1:G1");
				$event->sheet->setCellValue("A1", "ANALISIS DISTRIBUSI SALDO AKUN");
				$event->sheet->getStyle("A1")->applyFromArray([
					"font" => [
						"bold" => true,
						"size" => 14,
						"color" => ["rgb" => "6C3483"],
					],
					"alignment" => [
						"horizontal" => Alignment::HORIZONTAL_CENTER,
						"vertical" => Alignment::VERTICAL_CENTER,
					],
					"fill" => [
						"fillType" => Fill::FILL_SOLID,
						"color" => ["rgb" => "F5EEF8"],
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

		// Create a doughnut chart for account distribution
		$chart = new \PhpOffice\PhpSpreadsheet\Chart\Chart(
			"accountChart",
			new \PhpOffice\PhpSpreadsheet\Chart\Title("Distribusi Saldo Akun"),
			null,
			null,
			[
				new \PhpOffice\PhpSpreadsheet\Chart\DataSeries(
					\PhpOffice\PhpSpreadsheet\Chart\DataSeries::TYPE_DOUGHNUTCHART,
					null,
					range(0, count($data) - 2),
					[
						new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues(
							"String",
							"'Akun'!\$A\$3:\$A\$" . ($lastRow - 1)
						),
						new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues(
							"Number",
							"'Akun'!\$B\$3:\$B\$" . ($lastRow - 1)
						),
					]
				),
			]
		);

		$chart->setTopLeftPosition("H2");
		$chart->setBottomRightPosition("N15");

		$event->sheet->getDelegate()->addChart($chart);
	}

	private function formatCurrency($value)
	{
		$amount = is_numeric($value) ? $value / 100 : 0;
		return "Rp " . number_format($amount, 0, ",", ".");
	}

	private function getAccountType($accountName)
	{
		$accountLower = strtolower($accountName);

		if (
			strpos($accountLower, "tabung") !== false ||
			strpos($accountLower, "deposit") !== false
		) {
			return "Tabungan";
		}

		if (
			strpos($accountLower, "tunai") !== false ||
			strpos($accountLower, "cash") !== false
		) {
			return "Tunai";
		}

		if (
			strpos($accountLower, "kartu") !== false ||
			strpos($accountLower, "credit") !== false
		) {
			return "Kartu";
		}

		if (strpos($accountLower, "invest") !== false) {
			return "Investasi";
		}

		return "Lainnya";
	}

	private function getAccountGrowth($accountName, $currentBalance)
	{
		// Simulasi pertumbuhan (bisa diganti dengan data historis)
		$growthRate = match ($this->getAccountType($accountName)) {
			"Tabungan" => 0.05,
			"Investasi" => 0.1,
			"Kartu" => -0.02,
			default => 0.01,
		};

		return $currentBalance * $growthRate;
	}

	private function getHealthIndicator($balance)
	{
		if ($balance > 0) {
			return "✓";
		} elseif ($balance < 0) {
			return "⚠";
		} else {
			return "○";
		}
	}
}
