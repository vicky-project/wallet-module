<?php

namespace Modules\Wallet\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SummarySheet implements FromArray, WithTitle, WithHeadings, WithStyles
{
	protected $reportData;

	public function __construct(array $reportData)
	{
		$this->reportData = $reportData;
	}

	public function array(): array
	{
		$summary = $this->reportData["report_data"]["financial_summary"] ?? [];
		$incomeNumber = $summary["income_number"] ?? 0;
		$expenseNumber = $summary["expense_number"] ?? 0;
		$netFlow = $summary["net_flow"] ?? 0;
		$incomeCount = $summary["income_count"] ?? 0;
		$expenseCount = $summary["expense_count"] ?? 0;
		$totalTransfer = $summary["total_transfer"] ?? 0;

		// Hitung persentase transaksi
		$totalTransactions = $incomeCount + $expenseCount;
		$incomePercentage =
			$totalTransactions > 0 ? ($incomeCount / $totalTransactions) * 100 : 0;
		$expensePercentage =
			$totalTransactions > 0 ? ($expenseCount / $totalTransactions) * 100 : 0;

		$data = [
			// ============ HEADER ============
			["LAPORAN RINGKASAN KEUANGAN"],
			[""],

			// ============ RINGKASAN UTAMA ============
			["RINGKASAN UTAMA"],
			["Pendapatan Total", $this->formatCurrency($incomeNumber)],
			["Pengeluaran Total", $this->formatCurrency($expenseNumber)],
			["Saldo Bersih", $this->formatCurrency($netFlow)],
			[""],

			// ============ STATISTIK TRANSAKSI ============
			["STATISTIK TRANSAKSI"],
			[
				"Jumlah Transaksi Pendapatan",
				$incomeCount,
				number_format($incomePercentage, 1) . "%",
			],
			[
				"Jumlah Transaksi Pengeluaran",
				$expenseCount,
				number_format($expensePercentage, 1) . "%",
			],
			["Total Transfer", $this->formatCurrency($totalTransfer)],
			[""],

			// ============ ANALISIS SEDERHANA ============
			["ANALISIS"],
			["Status Keuangan", $this->getFinancialStatus($netFlow)],
			[
				"Rasio Pengeluaran/Pendapatan",
				$this->getExpenseRatio($incomeNumber, $expenseNumber),
			],
			[""],

			// ============ INFORMASI LAPORAN ============
			["INFORMASI LAPORAN"],
			[
				"Periode",
				$this->extractPeriodDate($this->reportData["period"] ?? []) ??
				"Semua Periode",
			],
			["Mata Uang", $this->reportData["currency"] ?? "IDR"],
			[
				"Tanggal Ekspor",
				$this->reportData["exported_at"] ?? now()->format("Y-m-d H:i:s"),
			],
		];

		return $data;
	}

	public function headings(): array
	{
		return [];
	}

	public function title(): string
	{
		return "Ringkasan";
	}

	public function styles(Worksheet $sheet)
	{
		$data = $this->array();
		$lastRow = count($data);

		// ============ SET COLUMN WIDTHS ============
		$sheet->getColumnDimension("A")->setWidth(30);
		$sheet->getColumnDimension("B")->setWidth(20);
		$sheet->getColumnDimension("C")->setWidth(15);

		// ============ MAIN TITLE ============
		$sheet
			->getStyle("A1")
			->getFont()
			->setBold(true)
			->setSize(14);

		$sheet->mergeCells("A1:C1");
		$sheet
			->getStyle("A1")
			->getAlignment()
			->setHorizontal("center");

		// Background judul utama
		$sheet
			->getStyle("A1")
			->getFill()
			->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
			->getStartColor()
			->setARGB("FF34495E");

		$sheet
			->getStyle("A1")
			->getFont()
			->getColor()
			->setARGB("FFFFFFFF");

		$sheet->getRowDimension(1)->setRowHeight(25);

		// ============ SECTION HEADERS ============
		$sectionHeaders = [
			"RINGKASAN UTAMA" => 4,
			"STATISTIK TRANSAKSI" => 9,
			"ANALISIS" => 14,
			"INFORMASI LAPORAN" => 18,
		];

		foreach ($sectionHeaders as $section => $row) {
			if ($row <= $lastRow) {
				$sheet
					->getStyle("A{$row}")
					->getFont()
					->setBold(true)
					->setSize(12);

				// Background section header
				$sheet
					->getStyle("A{$row}")
					->getFill()
					->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
					->getStartColor()
					->setARGB("FFECF0F1");

				$sheet->getRowDimension($row)->setRowHeight(20);
			}
		}

		// ============ FORMAT ANGKAMATAUANG ============
		// Format angka untuk ringkasan utama
		for ($row = 5; $row <= 7; $row++) {
			$sheet
				->getStyle("B{$row}")
				->getNumberFormat()
				->setFormatCode("#,##0");
		}

		// Format angka untuk total transfer
		$sheet
			->getStyle("B12")
			->getNumberFormat()
			->setFormatCode("#,##0");

		// ============ WARNA UNTUK NILAI POSITIF/NEGATIF ============
		$netFlow =
			$this->reportData["report_data"]["financial_summary"]["net_flow"] ?? 0;
		$netRow = 7;

		// Warna untuk saldo bersih
		if ($netFlow > 0) {
			$sheet
				->getStyle("B{$netRow}")
				->getFont()
				->getColor()
				->setARGB("FF27AE60");
		} elseif ($netFlow < 0) {
			$sheet
				->getStyle("B{$netRow}")
				->getFont()
				->getColor()
				->setARGB("FFE74C3C");
		}

		// ============ BORDER UNTUK TABEL ============
		// Border untuk ringkasan utama
		$sheet
			->getStyle("A5:B7")
			->getBorders()
			->getAllBorders()
			->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

		// Border untuk statistik transaksi
		$sheet
			->getStyle("A10:C12")
			->getBorders()
			->getAllBorders()
			->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

		// Border untuk analisis
		$sheet
			->getStyle("A15:B16")
			->getBorders()
			->getAllBorders()
			->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

		// Border untuk informasi laporan
		$sheet
			->getStyle("A19:B22")
			->getBorders()
			->getAllBorders()
			->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

		// ============ ALIGNMENT ============
		// Semua label rata kiri
		$sheet
			->getStyle("A4:A{$lastRow}")
			->getAlignment()
			->setHorizontal("left")
			->setVertical("center");

		// Semua nilai/number rata kanan
		$sheet
			->getStyle("B5:B{$lastRow}")
			->getAlignment()
			->setHorizontal("right")
			->setVertical("center");

		// Persentase rata tengah
		$sheet
			->getStyle("C10:C12")
			->getAlignment()
			->setHorizontal("center")
			->setVertical("center");

		// Analisis dan informasi rata kiri
		$sheet
			->getStyle("B15:B16")
			->getAlignment()
			->setHorizontal("left");

		$sheet
			->getStyle("B19:B22")
			->getAlignment()
			->setHorizontal("left");

		// ============ FONT STYLING ============
		// Bold untuk nilai penting
		$importantRows = [5, 6, 7, 12]; // Pendapatan, pengeluaran, saldo, transfer
		foreach ($importantRows as $row) {
			if ($row <= $lastRow) {
				$sheet
					->getStyle("A{$row}:B{$row}")
					->getFont()
					->setBold(true);
			}
		}

		// Alternating row color untuk statistik transaksi
		$sheet
			->getStyle("A10:C10")
			->getFill()
			->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
			->getStartColor()
			->setARGB("FFF8F9F9");

		$sheet
			->getStyle("A12:C12")
			->getFill()
			->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
			->getStartColor()
			->setARGB("FFF8F9F9");

		// Row height konsisten
		for ($row = 1; $row <= $lastRow; $row++) {
			if ($row == 1) {
				$sheet->getRowDimension($row)->setRowHeight(25);
			} elseif (in_array($row, [4, 9, 14, 18])) {
				$sheet->getRowDimension($row)->setRowHeight(20);
			} else {
				$sheet->getRowDimension($row)->setRowHeight(18);
			}
		}
	}

	private function formatCurrency($value)
	{
		if (!is_numeric($value)) {
			return "0";
		}
		$amount = $value / 100;
		return $amount;
	}

	private function extractPeriodDate($period)
	{
		if (
			!is_array($period) ||
			!isset($period["start_date"]) ||
			!isset($period["end_date"])
		) {
			return "Semua Periode";
		}

		// Format tanggal agar lebih rapi
		$start = date("d M Y", strtotime($period["start_date"]));
		$end = date("d M Y", strtotime($period["end_date"]));

		return $start . " - " . $end;
	}

	private function getFinancialStatus($netFlow)
	{
		if ($netFlow > 0) {
			return "Surplus (Positif)";
		} elseif ($netFlow < 0) {
			return "Defisit (Negatif)";
		} else {
			return "Seimbang";
		}
	}

	private function getExpenseRatio($income, $expense)
	{
		if ($income == 0) {
			return "Tidak dapat dihitung";
		}

		$ratio = ($expense / $income) * 100;
		return number_format($ratio, 1) . "%";
	}
}
