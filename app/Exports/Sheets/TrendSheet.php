<?php

namespace Modules\Wallet\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;

class TrendSheet implements FromArray, WithTitle, WithHeadings, WithStyles
{
	protected $reportData;
	protected $trendData;
	protected $labels = [];
	protected $incomeData = [];
	protected $expenseData = [];

	public function __construct(array $reportData)
	{
		$this->reportData = $reportData;
		$this->trendData = $this->reportData["report_data"][
			"income_expense_trend"
		] ?? [
			"labels" => [],
			"datasets" => [["data" => []], ["data" => []]],
		];

		$this->labels = $this->trendData["labels"] ?? [];
		$this->incomeData = $this->trendData["datasets"][0]["data"] ?? [];
		$this->expenseData = $this->trendData["datasets"][1]["data"] ?? [];
	}

	public function array(): array
	{
		$data = [];

		// ============ HEADER SECTION ============
		$data[] = ["TREND PENDAPATAN VS PENGELUARAN"];
		$data[] = ["Analisis Perkembangan Keuangan dari Waktu ke Waktu"];
		$data[] = [""];

		// ============ RINGKASAN TREND ============
		$data[] = ["RINGKASAN TREND"];
		$data[] = ["Item", "Nilai", "Keterangan"];

		$totalIncome = array_sum($this->incomeData);
		$totalExpense = array_sum($this->expenseData);
		$totalNet = $totalIncome - $totalExpense;
		$totalPeriods = count($this->labels);

		$avgIncome = $totalPeriods > 0 ? $totalIncome / $totalPeriods : 0;
		$avgExpense = $totalPeriods > 0 ? $totalExpense / $totalPeriods : 0;
		$avgNet = $avgIncome - $avgExpense;

		// Hitung trend bulan ke bulan
		$incomeGrowth = $this->calculateGrowthTrend($this->incomeData);
		$expenseGrowth = $this->calculateGrowthTrend($this->expenseData);

		$data[] = [
			"Total Pendapatan",
			$this->formatCurrency($totalIncome),
			$this->getGrowthDescription($incomeGrowth),
		];
		$data[] = [
			"Total Pengeluaran",
			$this->formatCurrency($totalExpense),
			$this->getGrowthDescription($expenseGrowth),
		];
		$data[] = [
			"Saldo Bersih Total",
			$this->formatCurrency($totalNet),
			$this->getNetDescription($totalNet),
		];
		$data[] = [
			"Rata-rata Pendapatan/Bulan",
			$this->formatCurrency($avgIncome),
			$this->getStabilityDescription($this->incomeData),
		];
		$data[] = [
			"Rata-rata Pengeluaran/Bulan",
			$this->formatCurrency($avgExpense),
			$this->getStabilityDescription($this->expenseData),
		];
		$data[] = [
			"Rata-rata Saldo/Bulan",
			$this->formatCurrency($avgNet),
			$this->getNetDescription($avgNet),
		];
		$data[] = ["Jumlah Periode", $totalPeriods, "periode analisis"];

		$data[] = [""];

		// ============ TABEL DETAIL PERIODE ============
		$data[] = ["DETAIL PER PERIODE"];
		$data[] = [
			"Periode",
			"Pendapatan",
			"Pengeluaran",
			"Saldo Bersih",
			"Margin",
			"Status",
		];

		foreach ($this->labels as $index => $label) {
			$income = $this->incomeData[$index] ?? 0;
			$expense = $this->expenseData[$index] ?? 0;
			$net = $income - $expense;

			// Hitung margin (jika ada pendapatan)
			$margin = $income > 0 ? ($net / $income) * 100 : 0;

			$data[] = [
				$label,
				$this->formatCurrency($income),
				$this->formatCurrency($expense),
				$this->formatCurrency($net),
				number_format($margin, 1) . "%",
				$this->getPeriodStatus($net, $margin),
			];
		}

		if (count($this->labels) > 0) {
			// Baris total di akhir tabel
			$data[] = [
				"TOTAL",
				$this->formatCurrency($totalIncome),
				$this->formatCurrency($totalExpense),
				$this->formatCurrency($totalNet),
				$totalIncome > 0
					? number_format(($totalNet / $totalIncome) * 100, 1) . "%"
					: "0%",
				$this->getPeriodStatus(
					$totalNet,
					$totalIncome > 0 ? ($totalNet / $totalIncome) * 100 : 0
				),
			];
		} else {
			$data[] = ["Tidak ada data trend yang tersedia"];
		}

		$data[] = [""];

		// ============ ANALISIS PERFORMANCE ============
		$data[] = ["ANALISIS PERFORMANCE"];
		$data[] = ["Metrik", "Nilai", "Kategori"];

		$performanceMetrics = [
			[
				"Rasio Pengeluaran/Pendapatan",
				$this->getExpenseRatio($totalIncome, $totalExpense),
				$this->getRatioCategory($totalIncome, $totalExpense),
			],
			[
				"Volatilitas Pendapatan",
				$this->calculateVolatility($this->incomeData),
				$this->getVolatilityCategory($this->incomeData),
			],
			[
				"Volatilitas Pengeluaran",
				$this->calculateVolatility($this->expenseData),
				$this->getVolatilityCategory($this->expenseData),
			],
			[
				"Bulan dengan Pendapatan Tertinggi",
				$this->getMaxPeriod($this->incomeData, $this->labels),
				"Puncak Pendapatan",
			],
			[
				"Bulan dengan Pengeluaran Tertinggi",
				$this->getMaxPeriod($this->expenseData, $this->labels),
				"Puncak Pengeluaran",
			],
			[
				"Konsistensi Keuntungan",
				$this->getProfitConsistency($this->incomeData, $this->expenseData),
				"Tingkat Konsistensi",
			],
		];

		foreach ($performanceMetrics as $metric) {
			$data[] = $metric;
		}

		$data[] = [""];

		// ============ REKOMENDASI ============
		$data[] = ["REKOMENDASI BERDASARKAN TREND"];

		$recommendations = $this->generateRecommendations(
			$totalIncome,
			$totalExpense,
			$totalNet,
			$incomeGrowth,
			$expenseGrowth
		);
		foreach ($recommendations as $recommendation) {
			$data[] = ["• " . $recommendation];
		}

		return $data;
	}

	public function headings(): array
	{
		return [];
	}

	public function title(): string
	{
		return "Trend";
	}

	public function styles(Worksheet $sheet)
	{
		$data = $this->array();
		$lastRow = count($data);

		// ============ SET COLUMN WIDTHS ============
		$sheet->getColumnDimension("A")->setWidth(30);
		$sheet->getColumnDimension("B")->setWidth(20);
		$sheet->getColumnDimension("C")->setWidth(25);
		$sheet->getColumnDimension("D")->setWidth(15);
		$sheet->getColumnDimension("E")->setWidth(10);
		$sheet->getColumnDimension("F")->setWidth(20);

		// ============ MAIN TITLE ============
		$sheet
			->getStyle("A1")
			->getFont()
			->setBold(true)
			->setSize(16)
			->setName("Arial");

		$sheet->mergeCells("A1:F1");
		$sheet
			->getStyle("A1")
			->getAlignment()
			->setHorizontal(Alignment::HORIZONTAL_CENTER);

		$sheet
			->getStyle("A1")
			->getFill()
			->setFillType(Fill::FILL_SOLID)
			->getStartColor()
			->setARGB("FF2C3E50");

		$sheet
			->getStyle("A1")
			->getFont()
			->getColor()
			->setARGB("FFFFFFFF");

		$sheet->getRowDimension(1)->setRowHeight(30);

		// ============ SUBTITLE ============
		$sheet
			->getStyle("A2")
			->getFont()
			->setItalic(true)
			->setSize(11);

		$sheet->mergeCells("A2:F2");
		$sheet
			->getStyle("A2")
			->getAlignment()
			->setHorizontal(Alignment::HORIZONTAL_CENTER);
		$sheet
			->getStyle("A2")
			->getFont()
			->getColor()
			->setARGB("FF7F8C8D");

		$sheet->getRowDimension(2)->setRowHeight(20);

		// ============ SECTION HEADERS ============
		$sectionRows = [
			4,
			15,
			27 + count($this->labels),
			35 + count($this->labels),
		];

		foreach ($sectionRows as $row) {
			if ($row <= $lastRow && !empty($data[$row - 1][0])) {
				$sheet
					->getStyle("A{$row}")
					->getFont()
					->setBold(true)
					->setSize(12);

				$sheet->mergeCells("A{$row}:F{$row}");

				$sheet
					->getStyle("A{$row}")
					->getFill()
					->setFillType(Fill::FILL_SOLID)
					->getStartColor()
					->setARGB("FFECF0F1");

				$sheet->getRowDimension($row)->setRowHeight(22);
			}
		}

		// ============ TABLE HEADERS ============
		$tableHeaderRows = [5, 15, 27 + count($this->labels)];

		foreach ($tableHeaderRows as $row) {
			if ($row <= $lastRow) {
				$colEnd = !empty($data[$row - 1][5]) ? "F" : "C";

				$sheet
					->getStyle("A{$row}:{$colEnd}{$row}")
					->getFont()
					->setBold(true)
					->setSize(11);

				$sheet
					->getStyle("A{$row}:{$colEnd}{$row}")
					->getFill()
					->setFillType(Fill::FILL_SOLID)
					->getStartColor()
					->setARGB("FF3498DB");

				$sheet
					->getStyle("A{$row}:{$colEnd}{$row}")
					->getFont()
					->getColor()
					->setARGB("FFFFFFFF");

				$sheet->getRowDimension($row)->setRowHeight(20);
			}
		}

		// ============ DATA ROWS STYLING ============
		for ($row = 1; $row <= $lastRow; $row++) {
			// Format angka mata uang
			for ($col = 2; $col <= 4; $col++) {
				// Kolom B, C, D
				$colLetter = chr(64 + $col);
				$cellValue = $sheet->getCell("{$colLetter}{$row}")->getValue();

				if (is_numeric($cellValue) && $cellValue != 0) {
					$sheet
						->getStyle("{$colLetter}{$row}")
						->getNumberFormat()
						->setFormatCode("#,##0");

					// Warna untuk saldo bersih (kolom D)
					if ($col == 4) {
						if ($cellValue > 0) {
							$sheet
								->getStyle("{$colLetter}{$row}")
								->getFont()
								->getColor()
								->setARGB("FF27AE60");
						} elseif ($cellValue < 0) {
							$sheet
								->getStyle("{$colLetter}{$row}")
								->getFont()
								->getColor()
								->setARGB("FFE74C3C");
						}
					}
				}
			}

			// Alternating row colors untuk tabel detail
			if ($row >= 16 && $row <= 15 + count($this->labels) + 1) {
				$fillColor = $row % 2 == 0 ? "FFF8F9F9" : "FFFFFFFF";
				$sheet
					->getStyle("A{$row}:F{$row}")
					->getFill()
					->setFillType(Fill::FILL_SOLID)
					->getStartColor()
					->setARGB($fillColor);
			}

			// Warna untuk status di kolom F
			$statusCell = $sheet->getCell("F{$row}")->getValue();
			if (!empty($statusCell)) {
				if (
					strpos($statusCell, "BAIK") !== false ||
					strpos($statusCell, "SURPLUS") !== false
				) {
					$sheet
						->getStyle("F{$row}")
						->getFill()
						->setFillType(Fill::FILL_SOLID)
						->getStartColor()
						->setARGB("FFD5F4E6");
				} elseif (
					strpos($statusCell, "PERHATIAN") !== false ||
					strpos($statusCell, "DEFISIT") !== false
				) {
					$sheet
						->getStyle("F{$row}")
						->getFill()
						->setFillType(Fill::FILL_SOLID)
						->getStartColor()
						->setARGB("FFFADBD8");
				}
			}
		}

		// ============ BORDERS ============
		// Border untuk ringkasan trend
		$sheet
			->getStyle("A5:C14")
			->getBorders()
			->getAllBorders()
			->setBorderStyle(Border::BORDER_THIN);

		// Border untuk detail periode
		$detailStart = 15;
		$detailEnd = 15 + count($this->labels) + 1;
		$sheet
			->getStyle("A{$detailStart}:F{$detailEnd}")
			->getBorders()
			->getAllBorders()
			->setBorderStyle(Border::BORDER_THIN);

		// Border untuk analisis performance
		$performanceStart = 25;
		$performanceEnd = $performanceStart + 7;
		$sheet
			->getStyle("A{$performanceStart}:C{$performanceEnd}")
			->getBorders()
			->getAllBorders()
			->setBorderStyle(Border::BORDER_THIN);

		// ============ ALIGNMENT ============
		// Angka rata kanan
		$sheet
			->getStyle("B6:F" . $lastRow)
			->getAlignment()
			->setHorizontal(Alignment::HORIZONTAL_RIGHT)
			->setVertical(Alignment::VERTICAL_CENTER);

		// Label rata kiri
		$sheet
			->getStyle("A6:A" . $lastRow)
			->getAlignment()
			->setHorizontal(Alignment::HORIZONTAL_LEFT)
			->setVertical(Alignment::VERTICAL_CENTER);

		// Status dan kategori rata tengah
		$sheet
			->getStyle("F16:F" . $detailEnd)
			->getAlignment()
			->setHorizontal(Alignment::HORIZONTAL_CENTER);

		// Rekomendasi
		$recommendationStart = 35 + count($this->labels);
		$sheet
			->getStyle("A{$recommendationStart}:A" . $lastRow)
			->getFont()
			->setSize(11);

		// ============ SPECIAL HIGHLIGHTS ============
		// Bold untuk total baris
		$totalRow = 15 + count($this->labels) + 1;
		if ($totalRow <= $lastRow) {
			$sheet
				->getStyle("A{$totalRow}:F{$totalRow}")
				->getFont()
				->setBold(true);

			$sheet
				->getStyle("A{$totalRow}:F{$totalRow}")
				->getFill()
				->setFillType(Fill::FILL_SOLID)
				->getStartColor()
				->setARGB("FFFDEBD0");
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

	// ============ HELPER METHODS ============

	private function calculateGrowthTrend($data)
	{
		if (count($data) < 2) {
			return 0;
		}

		$first = $data[0] ?? 0;
		$last = $data[count($data) - 1] ?? 0;

		if ($first == 0) {
			return $last > 0 ? 100 : 0;
		}

		return (($last - $first) / $first) * 100;
	}

	private function getGrowthDescription($growth)
	{
		if ($growth > 20) {
			return "↑↑ Pertumbuhan Sangat Kuat";
		}
		if ($growth > 10) {
			return "↑ Pertumbuhan Kuat";
		}
		if ($growth > 0) {
			return "↑ Sedikit Naik";
		}
		if ($growth == 0) {
			return "→ Stabil";
		}
		if ($growth > -10) {
			return "↓ Sedikit Turun";
		}
		return "↓↓ Penurunan Signifikan";
	}

	private function getNetDescription($net)
	{
		if ($net > 0) {
			return "SURPLUS - Kondisi Sehat";
		}
		if ($net < 0) {
			return "DEFISIT - Perlu Perhatian";
		}
		return "SEIMBANG - Pendapatan = Pengeluaran";
	}

	private function getStabilityDescription($data)
	{
		if (count($data) < 2) {
			return "Data Tidak Cukup";
		}

		$stdDev = $this->calculateStdDev($data);
		$mean = array_sum($data) / count($data);
		$coeffVar = $mean > 0 ? ($stdDev / $mean) * 100 : 0;

		if ($coeffVar < 10) {
			return "Sangat Stabil";
		}
		if ($coeffVar < 25) {
			return "Stabil";
		}
		if ($coeffVar < 50) {
			return "Cukup Stabil";
		}
		return "Fluktuatif";
	}

	private function calculateStdDev($data)
	{
		$n = count($data);
		if ($n < 2) {
			return 0;
		}

		$mean = array_sum($data) / $n;
		$sumSquares = 0;

		foreach ($data as $value) {
			$sumSquares += pow($value - $mean, 2);
		}

		return sqrt($sumSquares / ($n - 1));
	}

	private function getPeriodStatus($net, $margin)
	{
		if ($net > 0) {
			if ($margin > 30) {
				return "BAIK - Margin Tinggi";
			}
			if ($margin > 15) {
				return "CUKUP - Margin Sedang";
			}
			return "HATI-HATI - Margin Tipis";
		} else {
			return "PERHATIAN - Defisit";
		}
	}

	private function getExpenseRatio($income, $expense)
	{
		if ($income == 0) {
			return "Tidak Terdefinisi";
		}
		return number_format(($expense / $income) * 100, 1) . "%";
	}

	private function getRatioCategory($income, $expense)
	{
		if ($income == 0) {
			return "Tidak Ada Pendapatan";
		}

		$ratio = ($expense / $income) * 100;

		if ($ratio < 70) {
			return "Sangat Efisien";
		}
		if ($ratio < 85) {
			return "Efisien";
		}
		if ($ratio < 95) {
			return "Cukup Efisien";
		}
		if ($ratio <= 100) {
			return "Batas Aman";
		}
		return "Tidak Efisien";
	}

	private function calculateVolatility($data)
	{
		if (count($data) < 2) {
			return "0%";
		}

		$stdDev = $this->calculateStdDev($data);
		$mean = array_sum($data) / count($data);

		if ($mean == 0) {
			return "0%";
		}

		$volatility = ($stdDev / $mean) * 100;
		return number_format($volatility, 1) . "%";
	}

	private function getVolatilityCategory($data)
	{
		if (count($data) < 2) {
			return "Data Tidak Cukup";
		}

		$stdDev = $this->calculateStdDev($data);
		$mean = array_sum($data) / count($data);

		if ($mean == 0) {
			return "Stabil";
		}

		$coeffVar = ($stdDev / $mean) * 100;

		if ($coeffVar < 10) {
			return "Sangat Stabil";
		}
		if ($coeffVar < 25) {
			return "Stabil";
		}
		if ($coeffVar < 50) {
			return "Cukup Stabil";
		}
		return "Sangat Volatile";
	}

	private function getMaxPeriod($data, $labels)
	{
		if (count($data) == 0) {
			return "Tidak Ada Data";
		}

		$maxIndex = array_keys($data, max($data))[0];
		return $labels[$maxIndex] .
			" (" .
			$this->formatCurrency($data[$maxIndex]) .
			")";
	}

	private function getProfitConsistency($incomeData, $expenseData)
	{
		if (count($incomeData) == 0) {
			return "Tidak Ada Data";
		}

		$profitMonths = 0;
		for ($i = 0; $i < count($incomeData); $i++) {
			if (($incomeData[$i] ?? 0) > ($expenseData[$i] ?? 0)) {
				$profitMonths++;
			}
		}

		$consistency = ($profitMonths / count($incomeData)) * 100;

		if ($consistency == 100) {
			return "SEMPURNA (100%)";
		}
		if ($consistency >= 80) {
			return "TINGGI (" . number_format($consistency, 0) . "%)";
		}
		if ($consistency >= 60) {
			return "CUKUP (" . number_format($consistency, 0) . "%)";
		}
		if ($consistency >= 40) {
			return "RENDAH (" . number_format($consistency, 0) . "%)";
		}
		return "SANGAT RENDAH (" . number_format($consistency, 0) . "%)";
	}

	private function generateRecommendations(
		$income,
		$expense,
		$net,
		$incomeGrowth,
		$expenseGrowth
	) {
		$recommendations = [];

		if ($net < 0) {
			$recommendations[] =
				"Perlu pengurangan pengeluaran atau peningkatan pendapatan untuk menghindari defisit";
		}

		if ($expenseGrowth > $incomeGrowth && $incomeGrowth > 0) {
			$recommendations[] =
				"Pertumbuhan pengeluaran lebih cepat dari pendapatan, perlu pengendalian biaya";
		}

		if ($incomeGrowth > 15) {
			$recommendations[] =
				"Pertumbuhan pendapatan yang kuat, pertahankan momentum ini";
		} elseif ($incomeGrowth < 0) {
			$recommendations[] =
				"Pendapatan mengalami penurunan, evaluasi strategi pendapatan";
		}

		$expenseRatio = $income > 0 ? ($expense / $income) * 100 : 0;
		if ($expenseRatio > 90) {
			$recommendations[] =
				"Rasio pengeluaran terhadap pendapatan terlalu tinggi (>90%), perlu efisiensi";
		}

		if (count($this->incomeData) >= 3) {
			$last3Income = array_slice($this->incomeData, -3);
			$trend = $this->calculateGrowthTrend($last3Income);
			if ($trend < -10) {
				$recommendations[] =
					"Trend pendapatan 3 bulan terakhir menurun signifikan";
			}
		}

		if (count($recommendations) == 0) {
			$recommendations[] =
				"Kondisi keuangan sehat, pertahankan manajemen yang baik";
			$recommendations[] =
				"Lanjutkan monitoring rutin untuk mempertahankan performa";
		}

		return $recommendations;
	}
}
