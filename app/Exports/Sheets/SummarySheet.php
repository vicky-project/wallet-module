<?php

namespace Modules\Wallet\Exports\Sheets;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SummarySheet extends BaseSheet implements WithStyles, WithHeadings
{
	public function __construct(array $reportData)
	{
		parent::__construct($reportData);
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
			->setFillType(Fill::FILL_SOLID)
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
			"RINGKASAN UTAMA" => 3,
			"STATISTIK TRANSAKSI" => 8,
			"ANALISIS" => 13,
			"INFORMASI LAPORAN" => 17,
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
					->setFillType(Fill::FILL_SOLID)
					->getStartColor()
					->setARGB("FFECF0F1");

				$sheet->getRowDimension($row)->setRowHeight(20);
			}
		}

		// ============ FORMAT ANGKAMATAUANG ============
		// Format angka untuk ringkasan utama
		for ($row = 4; $row <= 6; $row++) {
			$sheet
				->getStyle("B{$row}")
				->getNumberFormat()
				->setFormatCode("#,##0");
		}

		// Format angka untuk total transfer
		$sheet
			->getStyle("B11")
			->getNumberFormat()
			->setFormatCode("#,##0");

		// ============ WARNA UNTUK NILAI POSITIF/NEGATIF ============
		$netFlow =
			$this->reportData["report_data"]["financial_summary"]["net_flow"] ?? 0;
		$netRow = 6;

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
			->getStyle("A4:B6")
			->getBorders()
			->getAllBorders()
			->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

		// Border untuk statistik transaksi
		$sheet
			->getStyle("A9:C11")
			->getBorders()
			->getAllBorders()
			->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

		// Border untuk analisis
		$sheet
			->getStyle("A14:B15")
			->getBorders()
			->getAllBorders()
			->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

		// Border untuk informasi laporan
		$sheet
			->getStyle("A18:B20")
			->getBorders()
			->getAllBorders()
			->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

		// ============ ALIGNMENT ============
		// Semua label rata kiri
		$sheet
			->getStyle("A3:A{$lastRow}")
			->getAlignment()
			->setHorizontal("left")
			->setVertical("center");

		// Semua nilai/number rata kanan
		$sheet
			->getStyle("B4:B{$lastRow}")
			->getAlignment()
			->setHorizontal("right")
			->setVertical("center");

		// Persentase rata tengah
		$sheet
			->getStyle("C9:C11")
			->getAlignment()
			->setHorizontal("center")
			->setVertical("center");

		// Analisis dan informasi rata kiri
		$sheet
			->getStyle("B14:B15")
			->getAlignment()
			->setHorizontal("left");

		$sheet
			->getStyle("B18:B20")
			->getAlignment()
			->setHorizontal("left");

		// ============ FONT STYLING ============
		// Bold untuk nilai penting
		$importantRows = [4, 5, 6, 11]; // Pendapatan, pengeluaran, saldo, transfer
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
			->getStyle("A9:C9")
			->getFill()
			->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
			->getStartColor()
			->setARGB("FFF8F9F9");

		$sheet
			->getStyle("A11:C11")
			->getFill()
			->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
			->getStartColor()
			->setARGB("FFF8F9F9");

		// Row height konsisten
		for ($row = 1; $row <= $lastRow; $row++) {
			if ($row == 1) {
				$sheet->getRowDimension($row)->setRowHeight(25);
			} elseif (in_array($row, [3, 8, 13, 17])) {
				$sheet->getRowDimension($row)->setRowHeight(20);
			} else {
				$sheet->getRowDimension($row)->setRowHeight(18);
			}
		}
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
