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

class BudgetSheet implements
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
		$budgetData = $this->reportData["report_data"]["budget_analysis"] ?? [];
		$labels = $budgetData["labels"] ?? [];
		$budgetValues = $budgetData["datasets"][0]["data"] ?? [];
		$spentValues = $budgetData["datasets"][1]["data"] ?? [];

		$data = [];

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
				$this->getProgressBar($usagePercentage),
			];
		}

		// Add summary row
		$totalBudget = array_sum($budgetValues);
		$totalSpent = array_sum($spentValues);
		$totalRemaining = $totalBudget - $totalSpent;
		$totalPercentage =
			$totalBudget > 0 ? ($totalSpent / $totalBudget) * 100 : 0;

		$data[] = [
			"TOTAL",
			$this->formatCurrency($totalBudget),
			$this->formatCurrency($totalSpent),
			$this->formatCurrency($totalRemaining),
			number_format($totalPercentage, 2) . "%",
			$this->getUsageStatus($totalPercentage),
			"■■■■■■■■■■",
		];

		return $data;
	}

	public function headings(): array
	{
		return [
			"Anggaran",
			"Direncanakan",
			"Terpakai",
			"Sisa",
			"Penggunaan",
			"Status",
			"Progress",
		];
	}

	public function title(): string
	{
		return "Anggaran";
	}

	public function styles(Worksheet $sheet)
	{
		$lastRow = count($this->array()) + 1;

		return [
			1 => [
				"font" => ["bold" => true, "size" => 12],
				"fill" => [
					"fillType" => Fill::FILL_SOLID,
					"color" => ["rgb" => "27AE60"],
				],
				"font" => ["color" => ["rgb" => "FFFFFF"]],
			],
			"A{$lastRow}:G{$lastRow}" => [
				"font" => ["bold" => true],
				"fill" => [
					"fillType" => Fill::FILL_SOLID,
					"color" => ["rgb" => "E8F8F5"],
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
			"F2:F{$lastRow}" => [
				"alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER],
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
				$event->sheet->getColumnDimension("C")->setWidth(20);
				$event->sheet->getColumnDimension("D")->setWidth(20);
				$event->sheet->getColumnDimension("E")->setWidth(15);
				$event->sheet->getColumnDimension("F")->setWidth(15);
				$event->sheet->getColumnDimension("G")->setWidth(20);

				// Add title
				$event->sheet->mergeCells("A1:G1");
				$event->sheet->setCellValue("A1", "ANALISIS ANGGARAN VS REALISASI");
				$event->sheet->getStyle("A1")->applyFromArray([
					"font" => [
						"bold" => true,
						"size" => 14,
						"color" => ["rgb" => "186A3B"],
					],
					"alignment" => [
						"horizontal" => Alignment::HORIZONTAL_CENTER,
						"vertical" => Alignment::VERTICAL_CENTER,
					],
					"fill" => [
						"fillType" => Fill::FILL_SOLID,
						"color" => ["rgb" => "E8F6F3"],
					],
				]);

				// Insert actual data starting from row 3
				$data = $this->array();
				$event->sheet->fromArray($data, null, "A3", true);

				// Add conditional formatting for usage percentage
				$lastRow = count($data) + 2;
				$conditional1 = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
				$conditional1->setConditionType(
					\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_CELLIS
				);
				$conditional1->setOperatorType(
					\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_LESSTHAN
				);
				$conditional1->addCondition("70");
				$conditional1
					->getStyle()
					->getFill()
					->setFillType(Fill::FILL_SOLID)
					->getEndColor()
					->setARGB("FFC8E6C9");

				$conditional2 = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
				$conditional2->setConditionType(
					\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_CELLIS
				);
				$conditional2->setOperatorType(
					\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_BETWEEN
				);
				$conditional2->addCondition("70");
				$conditional2->addCondition("90");
				$conditional2
					->getStyle()
					->getFill()
					->setFillType(Fill::FILL_SOLID)
					->getEndColor()
					->setARGB("FFFFF9C4");

				$conditional3 = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
				$conditional3->setConditionType(
					\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_CELLIS
				);
				$conditional3->setOperatorType(
					\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_GREATERTHANOREQUAL
				);
				$conditional3->addCondition("90");
				$conditional3
					->getStyle()
					->getFill()
					->setFillType(Fill::FILL_SOLID)
					->getEndColor()
					->setARGB("FFFFCDD2");

				$conditionalStyles = $event->sheet
					->getStyle("E3:E{$lastRow}")
					->getConditionalStyles();
				$conditionalStyles[] = $conditional1;
				$conditionalStyles[] = $conditional2;
				$conditionalStyles[] = $conditional3;

				$event->sheet
					->getStyle("E3:E{$lastRow}")
					->setConditionalStyles($conditionalStyles);

				// Add chart
				$this->addChart($event);
			},
		];
	}

	private function addChart(AfterSheet $event)
	{
		$data = $this->array();
		$lastRow = count($data) + 2;

		// Create a bar chart for budget vs spent
		$chart = new \PhpOffice\PhpSpreadsheet\Chart\Chart(
			"budgetChart",
			new \PhpOffice\PhpSpreadsheet\Chart\Title("Anggaran vs Realisasi"),
			null,
			null,
			[
				new \PhpOffice\PhpSpreadsheet\Chart\DataSeries(
					\PhpOffice\PhpSpreadsheet\Chart\DataSeries::TYPE_BARCHART,
					null,
					range(0, count($data) - 2),
					[
						new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues(
							"String",
							"'Anggaran'!\$A\$3:\$A\$" . ($lastRow - 1)
						),
						new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues(
							"Number",
							"'Anggaran'!\$B\$3:\$B\$" . ($lastRow - 1)
						),
						new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues(
							"Number",
							"'Anggaran'!\$C\$3:\$C\$" . ($lastRow - 1)
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

	private function getProgressBar($percentage)
	{
		$filled = round($percentage / 10);
		$progress = str_repeat("■", $filled) . str_repeat("□", 10 - $filled);
		return $progress;
	}
}
