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

		// Hitung persentase
		$totalTransactions = $incomeCount + $expenseCount;
		$incomePercentage =
			$totalTransactions > 0 ? ($incomeCount / $totalTransactions) * 100 : 0;
		$expensePercentage =
			$totalTransactions > 0 ? ($expenseCount / $totalTransactions) * 100 : 0;

		// Hitung rasio pengeluaran/pendapatan
		$expenseToIncomeRatio =
			$incomeNumber > 0 ? ($expenseNumber / $incomeNumber) * 100 : 0;

		$data = [
			["RINGKASAN KEUANGAN - LAPORAN KEUANGAN"],
			[""],

			// ============ STATISTIK UTAMA ============
			["STATISTIK UTAMA", "", "", "TREND & ANALISIS"],
			[
				"Pendapatan",
				$this->formatCurrency($incomeNumber),
				"",
				$this->getTrendIndicator($netFlow),
			],
			[
				"Pengeluaran",
				$this->formatCurrency($expenseNumber),
				"",
				"Rasio: " . number_format($expenseToIncomeRatio, 1) . "%",
			],
			[
				"Saldo Bersih",
				$this->formatCurrency($netFlow),
				"",
				$this->getNetStatus($netFlow),
			],
			[""],

			// ============ DETAIL TRANSAKSI ============
			["DETAIL TRANSAKSI", "Jumlah", "Persentase"],
			["Pendapatan", $incomeCount, number_format($incomePercentage, 1) . "%"],
			[
				"Pengeluaran",
				$expenseCount,
				number_format($expensePercentage, 1) . "%",
			],
			["Total Transaksi", $totalTransactions, "100%"],
			["Transfer Dana", $this->formatCurrency($totalTransfer), "-"],
			[""],

			// ============ PERFORMANCE INDICATORS ============
			["PERFORMANCE INDICATORS", "Nilai", "Keterangan"],
			[
				"Cash Flow",
				$this->getCashFlowStatus($netFlow),
				$this->getCashFlowDescription($netFlow),
			],
			[
				"Efisiensi",
				$this->getEfficiencyScore($incomeNumber, $expenseNumber),
				$this->getEfficiencyLevel($incomeNumber, $expenseNumber),
			],
			[
				"Stabilitas",
				$this->getStabilityScore($incomeNumber, $expenseNumber),
				$this->getStabilityLevel($incomeNumber, $expenseNumber),
			],
			[""],

			// ============ INFORMASI LAPORAN ============
			["INFORMASI LAPORAN"],
			[
				"Periode Laporan",
				$this->extractPeriodDate($this->reportData["period"] ?? []) ??
				"Semua Periode",
			],
			["Mata Uang", $this->reportData["currency"] ?? "IDR"],
			[
				"Tanggal Ekspor",
				$this->reportData["exported_at"] ?? now()->format("Y-m-d H:i:s"),
			],
			["ID Laporan", "FIN-" . date("Ymd") . "-" . strtoupper(uniqid())],
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
		$sheet->getColumnDimension("A")->setWidth(28);
		$sheet->getColumnDimension("B")->setWidth(20);
		$sheet->getColumnDimension("C")->setWidth(15);
		$sheet->getColumnDimension("D")->setWidth(25);

		// ============ MAIN TITLE STYLE ============
		$sheet
			->getStyle("A1")
			->getFont()
			->setBold(true)
			->setSize(16)
			->setName("Arial");

		$sheet->mergeCells("A1:D1");
		$sheet
			->getStyle("A1")
			->getAlignment()
			->setHorizontal("center")
			->setVertical("center");

		$sheet
			->getStyle("A1")
			->getFill()
			->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
			->getStartColor()
			->setARGB("FF2C3E50");

		$sheet
			->getStyle("A1")
			->getFont()
			->getColor()
			->setARGB("FFFFFFFF");

		$sheet->getRowDimension(1)->setRowHeight(35);

		// ============ SECTION HEADERS ============
		$sectionRows = [4, 10, 16, 21]; // Row numbers for section headers

		foreach ($sectionRows as $row) {
			if ($row <= $lastRow && !empty($data[$row - 1][0])) {
				$sheet
					->getStyle("A{$row}:D{$row}")
					->getFont()
					->setBold(true)
					->setSize(12);

				$sheet->mergeCells("A{$row}:D{$row}");

				$sheet
					->getStyle("A{$row}")
					->getFill()
					->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
					->getStartColor()
					->setARGB("FFECF0F1");

				$sheet->getRowDimension($row)->setRowHeight(25);
			}
		}

		// ============ TABLE HEADERS ============
		$tableHeaderRows = [5, 11, 17]; // Row numbers for table headers

		foreach ($tableHeaderRows as $row) {
			if ($row <= $lastRow) {
				$colEnd = !empty($data[$row - 1][2]) ? "C" : "B";

				$sheet
					->getStyle("A{$row}:{$colEnd}{$row}")
					->getFont()
					->setBold(true)
					->setSize(11);

				$sheet
					->getStyle("A{$row}:{$colEnd}{$row}")
					->getFill()
					->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
					->getStartColor()
					->setARGB("FF3498DB");

				$sheet
					->getStyle("A{$row}:{$colEnd}{$row}")
					->getFont()
					->getColor()
					->setARGB("FFFFFFFF");

				$sheet->getRowDimension($row)->setRowHeight(22);
			}
		}

		// ============ DATA ROWS STYLING ============
		// Format currency for financial numbers
		for ($row = 6; $row <= $lastRow; $row++) {
			// Format kolom B untuk angka mata uang
			$cellValue = $sheet->getCell("B{$row}")->getValue();
			if (is_numeric($cellValue) && $cellValue != 0) {
				// Cek apakah ini baris dengan data mata uang (bukan count)
				if (!in_array($row, [12, 13])) {
					// Baris jumlah transaksi tidak diformat mata uang
					$sheet
						->getStyle("B{$row}")
						->getNumberFormat()
						->setFormatCode("#,##0");

					// Tambahkan warna untuk nilai positif/negatif
					if ($cellValue < 0) {
						$sheet
							->getStyle("B{$row}")
							->getFont()
							->getColor()
							->setARGB("FFE74C3C");
					} elseif ($cellValue > 0) {
						$sheet
							->getStyle("B{$row}")
							->getFont()
							->getColor()
							->setARGB("FF27AE60");
					}
				}
			}

			// Alternating row colors untuk tabel utama
			if ($row >= 6 && $row <= 9) {
				$sheet
					->getStyle("A{$row}:D{$row}")
					->getFill()
					->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
					->getStartColor()
					->setARGB($row % 2 == 0 ? "FFF8F9F9" : "FFFFFFFF");
			}

			// Alternating row colors untuk tabel detail
			if ($row >= 12 && $row <= 15) {
				$sheet
					->getStyle("A{$row}:C{$row}")
					->getFill()
					->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
					->getStartColor()
					->setARGB($row % 2 == 0 ? "FFF8F9F9" : "FFFFFFFF");
			}
		}

		// ============ BORDERS ============
		// Border untuk tabel utama
		$sheet
			->getStyle("A5:D9")
			->getBorders()
			->getAllBorders()
			->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

		// Border untuk tabel detail transaksi
		$sheet
			->getStyle("A11:C15")
			->getBorders()
			->getAllBorders()
			->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

		// Border untuk performance indicators
		$sheet
			->getStyle("A17:C20")
			->getBorders()
			->getAllBorders()
			->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

		// Border untuk informasi laporan
		$sheet
			->getStyle("A22:B25")
			->getBorders()
			->getAllBorders()
			->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

		// ============ ALIGNMENT ============
		// Center alignment untuk header dan angka
		$sheet
			->getStyle("B6:B25")
			->getAlignment()
			->setHorizontal("right")
			->setVertical("center");

		$sheet
			->getStyle("C11:C25")
			->getAlignment()
			->setHorizontal("center")
			->setVertical("center");

		$sheet
			->getStyle("D6:D9")
			->getAlignment()
			->setHorizontal("left")
			->setVertical("center");

		// Left alignment untuk label
		$sheet
			->getStyle("A6:A25")
			->getAlignment()
			->setHorizontal("left")
			->setVertical("center");

		// ============ SPECIAL HIGHLIGHTS ============
		// Highlight saldo bersih
		$netFlow =
			$this->reportData["report_data"]["financial_summary"]["net_flow"] ?? 0;
		$netRow = 8; // Row untuk saldo bersih

		if ($netFlow > 0) {
			$sheet
				->getStyle("A{$netRow}:B{$netRow}")
				->getFill()
				->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
				->getStartColor()
				->setARGB("FFD5F4E6");
		} elseif ($netFlow < 0) {
			$sheet
				->getStyle("A{$netRow}:B{$netRow}")
				->getFill()
				->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
				->getStartColor()
				->setARGB("FFFADBD8");
		}

		// Bold font untuk nilai penting
		$importantRows = [6, 7, 8]; // Pendapatan, Pengeluaran, Saldo Bersih
		foreach ($importantRows as $row) {
			$sheet
				->getStyle("A{$row}:B{$row}")
				->getFont()
				->setBold(true);
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

		return $period["start_date"] . " - " . $period["end_date"];
	}

	// ============ HELPER METHODS FOR ANALYSIS ============

	private function getTrendIndicator($netFlow)
	{
		if ($netFlow > 0) {
			return "↑ Positif (Surplus)";
		} elseif ($netFlow < 0) {
			return "↓ Negatif (Defisit)";
		} else {
			return "→ Seimbang";
		}
	}

	private function getNetStatus($netFlow)
	{
		if ($netFlow > 0) {
			return "Sehat - Pendapatan > Pengeluaran";
		} elseif ($netFlow < 0) {
			return "Perhatian - Pengeluaran > Pendapatan";
		} else {
			return "Seimbang - Pendapatan = Pengeluaran";
		}
	}

	private function getCashFlowStatus($netFlow)
	{
		if ($netFlow > 0) {
			return "Positif";
		} elseif ($netFlow < 0) {
			return "Negatif";
		} else {
			return "Netral";
		}
	}

	private function getCashFlowDescription($netFlow)
	{
		if ($netFlow > 0) {
			return "Pendapatan melebihi pengeluaran";
		} elseif ($netFlow < 0) {
			return "Pengeluaran melebihi pendapatan";
		} else {
			return "Pendapatan dan pengeluaran seimbang";
		}
	}

	private function getEfficiencyScore($income, $expense)
	{
		if ($income == 0) {
			return "0/10";
		}

		$ratio = $expense / $income;
		$score = 10 - min(10, $ratio * 10);
		return intval($score) . "/10";
	}

	private function getEfficiencyLevel($income, $expense)
	{
		if ($income == 0) {
			return "Tidak Tersedia";
		}

		$ratio = $expense / $income;
		if ($ratio < 0.3) {
			return "Sangat Efisien";
		}
		if ($ratio < 0.6) {
			return "Efisien";
		}
		if ($ratio < 0.9) {
			return "Cukup Efisien";
		}
		return "Kurang Efisien";
	}

	private function getStabilityScore($income, $expense)
	{
		if ($income + $expense == 0) {
			return "0/10";
		}

		$stability = 10 - (abs($income - $expense) / max($income, $expense)) * 10;
		return intval(max(0, $stability)) . "/10";
	}

	private function getStabilityLevel($income, $expense)
	{
		if ($income + $expense == 0) {
			return "Tidak Tersedia";
		}

		$difference = abs($income - $expense);
		$total = $income + $expense;
		$ratio = $difference / $total;

		if ($ratio < 0.2) {
			return "Sangat Stabil";
		}
		if ($ratio < 0.4) {
			return "Stabil";
		}
		if ($ratio < 0.6) {
			return "Cukup Stabil";
		}
		return "Kurang Stabil";
	}
}
