<?php

namespace Modules\Wallet\Exports\Sheets;

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
		// Ambil data kategori income dan expense dari struktur yang benar
		$categoryData = $this->reportData["report_data"]["category_analysis"] ?? [];

		// Data income (jika ada dalam struktur baru)
		$incomeData = $categoryData["income"] ?? $categoryData; // Fallback untuk struktur lama
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

			// Header untuk income
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

			// Total income
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

			// Header untuk expense
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

			// Total expense
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

		// Hitung totals dari kedua bagian
		$totalIncomeAmount = $this->getTotalFromCategoryData($incomeData);
		$totalExpenseAmount = $this->getTotalFromCategoryData($expenseData);
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
		$sheet->getColumnDimension("B")->setWidth(20);
		$sheet->getColumnDimension("C")->setWidth(15);
		$sheet->getColumnDimension("D")->setWidth(15);

		// Cari baris untuk setiap bagian
		$data = $this->array();
		$incomeTitleRow = 1;
		$expenseTitleRow = null;
		$summaryTitleRow = null;

		for ($i = 0; $i < count($data); $i++) {
			if (strpos($data[$i][0] ?? "", "ANALISIS PENGELUARAN") !== false) {
				$expenseTitleRow = $i + 1; // Excel row dimulai dari 1
			}
			if (strpos($data[$i][0] ?? "", "RINGKASAN KATEGORI") !== false) {
				$summaryTitleRow = $i + 1;
			}
		}

		// Style judul income
		$sheet
			->getStyle("A" . $incomeTitleRow)
			->getFont()
			->setBold(true)
			->setSize(14);
		$sheet->mergeCells("A" . $incomeTitleRow . ":D" . $incomeTitleRow);

		// Style judul expense
		if ($expenseTitleRow) {
			$sheet
				->getStyle("A" . $expenseTitleRow)
				->getFont()
				->setBold(true)
				->setSize(14);
			$sheet->mergeCells("A" . $expenseTitleRow . ":D" . $expenseTitleRow);
		}

		// Style judul summary
		if ($summaryTitleRow) {
			$sheet
				->getStyle("A" . $summaryTitleRow)
				->getFont()
				->setBold(true)
				->setSize(14);
			$sheet->mergeCells("A" . $summaryTitleRow . ":D" . $summaryTitleRow);
		}

		// Format angka untuk semua data numerik
		$lastRow = count($data);
		for ($row = 1; $row <= $lastRow; $row++) {
			// Format kolom B (jumlah) dan kolom D jika ada angka
			$cellValueB = $sheet->getCell("B" . $row)->getValue();
			if (is_numeric($cellValueB)) {
				$sheet
					->getStyle("B" . $row)
					->getNumberFormat()
					->setFormatCode("#,##0");
			}
		}

		// Border untuk tabel income (asumsi tabel income dari row 3 sampai sebelum expense)
		if ($expenseTitleRow) {
			$incomeTableStart = $incomeTitleRow + 2; // Setelah judul dan 1 baris kosong
			$incomeTableEnd = $expenseTitleRow - 3; // Sebelum baris kosong dan judul expense

			if ($incomeTableEnd > $incomeTableStart) {
				$sheet
					->getStyle("A" . $incomeTableStart . ":D" . $incomeTableEnd)
					->getBorders()
					->getAllBorders()
					->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
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
				}
			}
		}

		// Alignment
		$sheet
			->getStyle("A1:D" . $lastRow)
			->getAlignment()
			->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
		$sheet
			->getStyle("B2:B" . $lastRow)
			->getAlignment()
			->setHorizontal(
				\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT
			);
		$sheet
			->getStyle("C2:C" . $lastRow)
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

	private function getTotalFromCategoryData($categoryData)
	{
		if (empty($categoryData["datasets"][0]["data"])) {
			return 0;
		}
		return array_sum($categoryData["datasets"][0]["data"]);
	}
}
