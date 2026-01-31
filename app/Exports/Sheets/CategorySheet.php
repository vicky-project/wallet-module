<?php

namespace Modules\Wallet\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class CategorySheet implements FromArray, WithTitle, WithHeadings, WithStyles
{
	protected $reportData;
	protected $incomeLabels = [];
	protected $incomeValues = [];
	protected $expenseLabels = [];
	protected $expenseValues = [];
	protected $incomeTotal = 0;
	protected $expenseTotal = 0;

	public function __construct(array $reportData)
	{
		$this->reportData = $reportData;
	}

	public function array(): array
	{
		// Ambil data kategori income dan expense
		$categoryData = $this->reportData["report_data"]["category_analysis"] ?? [];

		// Data income dan expense
		$incomeData = $categoryData["income"] ?? $categoryData;
		$expenseData = $categoryData["expense"] ?? [];

		$data = [];

		// ==================== PENDAPATAN ====================
		$data[] = ["ANALISIS PENDAPATAN PER KATEGORI"];
		$data[] = [""];

		if (
			!empty($incomeData["labels"]) &&
			!empty($incomeData["datasets"][0]["data"])
		) {
			$incomeLabels = $incomeData["labels"] ?? [];
			$incomeValues = $incomeData["datasets"][0]["data"] ?? [];
			$totalIncome = array_sum($incomeValues);

			$this->incomeLabels = $incomeLabels;
			$this->incomeValues = $incomeValues;
			$this->incomeTotal = $totalIncome;

			$data[] = ["Kategori", "Jumlah", "Persentase", "Tipe"];

			foreach ($incomeLabels as $index => $label) {
				$amount = $incomeValues[$index] ?? 0;
				$percentage = $totalIncome > 0 ? ($amount / $totalIncome) * 100 : 0;

				$data[] = [
					$label,
					$this->formatCurrency($amount),
					number_format($percentage, 2) . "%",
					"Pendapatan",
				];
			}

			$data[] = [
				"TOTAL PENDAPATAN",
				$this->formatCurrency($totalIncome),
				"100%",
				"",
			];
		} else {
			$data[] = ["Tidak ada data pendapatan"];
		}

		$data[] = [""];
		$data[] = [""];

		// ==================== PENGELUARAN ====================
		$data[] = ["ANALISIS PENGELUARAN PER KATEGORI"];
		$data[] = [""];

		if (
			!empty($expenseData["labels"]) &&
			!empty($expenseData["datasets"][0]["data"])
		) {
			$expenseLabels = $expenseData["labels"] ?? [];
			$expenseValues = $expenseData["datasets"][0]["data"] ?? [];
			$totalExpense = array_sum($expenseValues);

			$this->expenseLabels = $expenseLabels;
			$this->expenseValues = $expenseValues;
			$this->expenseTotal = $totalExpense;

			$data[] = ["Kategori", "Jumlah", "Persentase", "Tipe"];

			foreach ($expenseLabels as $index => $label) {
				$amount = $expenseValues[$index] ?? 0;
				$percentage = $totalExpense > 0 ? ($amount / $totalExpense) * 100 : 0;

				$data[] = [
					$label,
					$this->formatCurrency($amount),
					number_format($percentage, 2) . "%",
					"Pengeluaran",
				];
			}

			$data[] = [
				"TOTAL PENGELUARAN",
				$this->formatCurrency($totalExpense),
				"100%",
				"",
			];
		} else {
			$data[] = ["Tidak ada data pengeluaran"];
		}

		// ==================== RINGKASAN ====================
		$data[] = [""];
		$data[] = [""];
		$data[] = ["RINGKASAN KATEGORI"];
		$data[] = [""];

		$totalIncomeAmount = $this->incomeTotal;
		$totalExpenseAmount = $this->expenseTotal;
		$netAmount = $totalIncomeAmount - $totalExpenseAmount;

		$data[] = ["Total Pendapatan", $this->formatCurrency($totalIncomeAmount)];
		$data[] = ["Total Pengeluaran", $this->formatCurrency($totalExpenseAmount)];
		$data[] = ["Selisih (Net)", $this->formatCurrency($netAmount)];

		$totalAll = $totalIncomeAmount + $totalExpenseAmount;
		if ($totalAll > 0) {
			$incomePercentage = ($totalIncomeAmount / $totalAll) * 100;
			$expensePercentage = ($totalExpenseAmount / $totalAll) * 100;

			$data[] = [
				"Persentase Pendapatan",
				number_format($incomePercentage, 2) . "%",
			];
			$data[] = [
				"Persentase Pengeluaran",
				number_format($expensePercentage, 2) . "%",
			];
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
		$sheet->getColumnDimension("B")->setWidth(15);
		$sheet->getColumnDimension("C")->setWidth(15);
		$sheet->getColumnDimension("D")->setWidth(15);

		// Cari baris untuk setiap bagian
		$data = $this->array();
		$incomeTitleRow = 1;
		$expenseTitleRow = null;
		$summaryTitleRow = null;

		// Temukan baris judul
		for ($i = 0; $i < count($data); $i++) {
			if (
				isset($data[$i][0]) &&
				strpos($data[$i][0], "ANALISIS PENGELUARAN") !== false
			) {
				$expenseTitleRow = $i + 1;
			}
			if (
				isset($data[$i][0]) &&
				strpos($data[$i][0], "RINGKASAN KATEGORI") !== false
			) {
				$summaryTitleRow = $i + 1;
			}
		}

		// ============ APPLY STYLES DENGAN METHOD CHAINING ============

		// Style judul income - PERBAIKAN DI SINI
		$sheet
			->getStyle("A" . $incomeTitleRow)
			->getFont()
			->setBold(true)
			->setSize(14);
		$sheet->mergeCells("A" . $incomeTitleRow . ":D" . $incomeTitleRow);
		$sheet
			->getStyle("A" . $incomeTitleRow)
			->getAlignment()
			->setHorizontal(Alignment::HORIZONTAL_CENTER);

		$sheet
			->getStyle("A" . $incomeTitleRow)
			->getFill()
			->setFillType(Fill::FILL_SOLID)
			->getStartColor()
			->setARGB("FF2C3E50");

		$sheet
			->getStyle("A" . $incomeTitleRow)
			->getFont()
			->getColor()
			->setARGB("FFFFFFFF");

		// Style judul expense
		if ($expenseTitleRow) {
			$sheet
				->getStyle("A" . $expenseTitleRow)
				->getFont()
				->setBold(true)
				->setSize(14);
			$sheet->mergeCells("A" . $expenseTitleRow . ":D" . $expenseTitleRow);
			$sheet
				->getStyle("A" . $expenseTitleRow)
				->getAlignment()
				->setHorizontal(Alignment::HORIZONTAL_CENTER);

			$sheet
				->getStyle("A" . $expenseTitleRow)
				->getFill()
				->setFillType(Fill::FILL_SOLID)
				->getStartColor()
				->setARGB("FF2C3E50");

			$sheet
				->getStyle("A" . $expenseTitleRow)
				->getFont()
				->getColor()
				->setARGB("FFFFFFFF");
		}

		// Style judul summary
		if ($summaryTitleRow) {
			$sheet
				->getStyle("A" . $summaryTitleRow)
				->getFont()
				->setBold(true)
				->setSize(14);
			$sheet->mergeCells("A" . $summaryTitleRow . ":D" . $summaryTitleRow);
			$sheet
				->getStyle("A" . $summaryTitleRow)
				->getAlignment()
				->setHorizontal("center")
				->setVertical("center");
		}

		// Format angka untuk semua data numerik di kolom B
		$lastRow = count($data);
		for ($row = 1; $row <= $lastRow; $row++) {
			$cellValueB = $sheet->getCell("B" . $row)->getValue();
			if (is_numeric($cellValueB)) {
				// PERBAIKAN: Gunakan setFormatCode langsung
				$sheet
					->getStyle("B" . $row)
					->getNumberFormat()
					->setFormatCode("#,##0");
			}
		}

		// Apply border dan alignment dengan method chaining yang benar
		$this->applyTableStyles(
			$sheet,
			$data,
			$incomeTitleRow,
			$expenseTitleRow,
			$summaryTitleRow
		);
	}

	private function applyTableStyles(
		$sheet,
		$data,
		$incomeTitleRow,
		$expenseTitleRow,
		$summaryTitleRow
	) {
		$lastRow = count($data);

		// Border untuk tabel income
		if ($expenseTitleRow) {
			$incomeTableStart = $incomeTitleRow + 2;
			$incomeTableEnd = $expenseTitleRow - 3;

			if ($incomeTableEnd > $incomeTableStart) {
				$sheet
					->getStyle("A" . $incomeTableStart . ":D" . $incomeTableEnd)
					->getBorders()
					->getAllBorders()
					->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

				// Alignment untuk tabel income
				$sheet
					->getStyle("A" . $incomeTableStart . ":D" . $incomeTableEnd)
					->getAlignment()
					->setVertical("center");

				// Alignment khusus untuk kolom
				$sheet
					->getStyle("B" . $incomeTableStart . ":B" . $incomeTableEnd)
					->getAlignment()
					->setHorizontal("right");

				$sheet
					->getStyle("C" . $incomeTableStart . ":C" . $incomeTableEnd)
					->getAlignment()
					->setHorizontal("center");
			}

			// Border untuk tabel expense
			$expenseTableStart = $expenseTitleRow + 2;
			$expenseTableEnd = $summaryTitleRow ? $summaryTitleRow - 3 : $lastRow;

			if ($expenseTableEnd > $expenseTableStart) {
				$sheet
					->getStyle("A" . $expenseTableStart . ":D" . $expenseTableEnd)
					->getBorders()
					->getAllBorders()
					->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

				// Alignment untuk tabel expense
				$sheet
					->getStyle("A" . $expenseTableStart . ":D" . $expenseTableEnd)
					->getAlignment()
					->setVertical("center");

				$sheet
					->getStyle("B" . $expenseTableStart . ":B" . $expenseTableEnd)
					->getAlignment()
					->setHorizontal("right");

				$sheet
					->getStyle("C" . $expenseTableStart . ":C" . $expenseTableEnd)
					->getAlignment()
					->setHorizontal("center");
			}

			// Border untuk tabel summary
			if ($summaryTitleRow) {
				$summaryTableStart = $summaryTitleRow + 2;
				$summaryTableEnd = $lastRow;

				if ($summaryTableEnd > $summaryTableStart) {
					$sheet
						->getStyle("A" . $summaryTableStart . ":B" . $summaryTableEnd)
						->getBorders()
						->getAllBorders()
						->setBorderStyle(
							\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
						);

					// Alignment untuk summary
					$sheet
						->getStyle("A" . $summaryTableStart . ":B" . $summaryTableEnd)
						->getAlignment()
						->setVertical("center");

					$sheet
						->getStyle("B" . $summaryTableStart . ":B" . $summaryTableEnd)
						->getAlignment()
						->setHorizontal("right");
				}
			}
		}

		// Font bold untuk header tabel
		$this->applyHeaderStyles($sheet, $data, $incomeTitleRow, $expenseTitleRow);
	}

	private function applyHeaderStyles(
		$sheet,
		$data,
		$incomeTitleRow,
		$expenseTitleRow
	) {
		// Header untuk income table (row ke-3 setelah judul)
		$incomeHeaderRow = $incomeTitleRow + 2;
		$sheet
			->getStyle("A" . $incomeHeaderRow . ":D" . $incomeHeaderRow)
			->getFont()
			->setBold(true);

		// Background untuk header income
		$sheet
			->getStyle("A" . $incomeHeaderRow . ":D" . $incomeHeaderRow)
			->getFill()
			->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
			->getStartColor()
			->setARGB("FF2E86C1");

		$sheet
			->getStyle("A" . $incomeHeaderRow . ":D" . $incomeHeaderRow)
			->getFont()
			->getColor()
			->setARGB("FFFFFFFF");

		// Header untuk expense table
		if ($expenseTitleRow) {
			$expenseHeaderRow = $expenseTitleRow + 2;
			$sheet
				->getStyle("A" . $expenseHeaderRow . ":D" . $expenseHeaderRow)
				->getFont()
				->setBold(true);

			$sheet
				->getStyle("A" . $expenseHeaderRow . ":D" . $expenseHeaderRow)
				->getFill()
				->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
				->getStartColor()
				->setARGB("FFE74C3C");

			$sheet
				->getStyle("A" . $expenseHeaderRow . ":D" . $expenseHeaderRow)
				->getFont()
				->getColor()
				->setARGB("FFFFFFFF");
		}
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
