<?php

namespace Modules\Wallet\Exports\Sheets;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Modules\Wallet\Models\Transaction;

class TransactionSheet extends BaseSheet implements WithEvents, WithHeadings
{
	protected $userId;
	protected $filters;

	public function __construct(array $reportData)
	{
		parent::__construct($reportData);
		// Ambil user_id dari data atau gunakan auth
		$this->userId = $reportData["user_id"] ?? auth()->id();
		$this->filters = $reportData["filters"] ?? [];
	}

	public function array(): array
	{
		$data = [];

		// Ambil data transaksi per tahun dari database
		$cacheKey =
			"transaction_export_{$this->userId}_" . md5(json_encode($this->filters));

		$yearsData = Cache::remember($cacheKey, 300, function () {
			return Transaction::getMonthlyTransactionData(
				$this->userId,
				$this->filters["account_id"] ?? null
			);
		});

		// Jika ada data per tahun
		if (!empty($yearsData)) {
			foreach ($yearsData as $year => $yearData) {
				// Judul untuk setiap tahun
				$data[] = ["TRANSAKSI TAHUN {$year}"];
				$data[] = [""]; // Baris kosong

				// Header tabel
				$data[] = [
					"Bulan",
					"Pendapatan",
					"Pengeluaran",
					"Saldo Bersih",
					"Transaksi",
					"Rata-rata/Transaksi",
				];

				// Data bulanan untuk tahun ini
				$this->addMonthlyDataForYear($data, $yearData, $year);

				// Baris kosong antar tahun
				$data[] = [""];
				$data[] = [""];
			}
		} else {
			// Fallback: data harian dari laporan biasa
			$transactionData =
				$this->reportData["report_data"]["transaction_analysis"] ?? [];
			$data = $this->getDailyTransactionData($transactionData);
		}

		// Tambahkan ringkasan semua tahun
		$this->addYearlySummary($data, $yearsData);

		return $data;
	}

	private function addMonthlyDataForYear(&$data, $yearData, $year)
	{
		$yearTotalIncome = 0;
		$yearTotalExpense = 0;
		$yearTotalTransactions = 0;

		$monthName = [
			1 => "January",
			2 => "February",
			3 => "March",
			4 => "April",
			5 => "May",
			6 => "June",
			7 => "July",
			8 => "August",
			9 => "September",
			10 => "October",
			11 => "November",
			12 => "December",
		];

		foreach ($yearData as $monthData) {
			$yearTotalIncome += $monthData->total_income;
			$yearTotalExpense += $monthData->total_expense;
			$yearTotalTransactions += $monthData->transaction_count;

			$monthStr = isset($monthName[$monthData["month"]])
				? $monthName[$monthData["month"]]
				: "Bulan {$monthData["month"]}";

			$data[] = [
				$monthStr,
				$this->formatCurrency($monthData->total_income),
				$this->formatCurrency($monthData->total_expense),
				$this->formatCurrency(
					$monthData->total_income - $monthData->total_expense
				),
				$monthData->transaction_count,
				$monthData->transaction_count > 0
					? $this->formatCurrency(
						($monthData->total_income + $monthData->total_expense) /
							$monthData->transaction_count
					)
					: 0,
			];
		}

		// Total untuk tahun ini
		$data[] = [
			"TOTAL " . $year,
			$this->formatCurrency($yearTotalIncome),
			$this->formatCurrency($yearTotalExpense),
			$this->formatCurrency($yearTotalIncome - $yearTotalExpense),
			$yearTotalTransactions,
			$yearTotalTransactions > 0
				? $this->formatCurrency(
					($yearTotalIncome + $yearTotalExpense) / $yearTotalTransactions
				)
				: 0,
		];
	}

	private function getDailyTransactionData($transactionData)
	{
		$data = [
			["AKTIVITAS TRANSAKSI PER HARI"],
			[""],
			[
				"Hari",
				"Pendapatan",
				"Pengeluaran",
				"Net",
				"Jumlah Transaksi",
				"Tipe Hari",
				"Tingkat Aktivitas",
			],
		];

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

		$transactionCounts = [];

		foreach ($labels as $index => $day) {
			$income = $incomeData[$index] ?? 0;
			$expense = $expenseData[$index] ?? 0;
			$net = $income - $expense;
			$count = $this->getTransactionCount($day);
			$transactionCounts[] = $count;

			$data[] = [
				$day,
				$this->formatCurrency($income),
				$this->formatCurrency($expense),
				$this->formatCurrency($net),
				$count,
				$this->getDayType($day),
				$this->getActivityLevel($income + $expense),
			];
		}

		// Total dan rata-rata
		$totalIncome = array_sum($incomeData);
		$totalExpense = array_sum($expenseData);

		$data[] = [
			"RATA-RATA",
			$this->formatCurrency($totalIncome / 7),
			$this->formatCurrency($totalExpense / 7),
			$this->formatCurrency(($totalIncome - $totalExpense) / 7),
			round(array_sum($transactionCounts) / 7, 1),
			"",
			"",
		];

		$data[] = [
			"TOTAL",
			$this->formatCurrency($totalIncome),
			$this->formatCurrency($totalExpense),
			$this->formatCurrency($totalIncome - $totalExpense),
			array_sum($transactionCounts),
			"",
			"",
		];

		return $data;
	}

	private function addYearlySummary(&$data, $yearsData)
	{
		if (empty($yearsData)) {
			return;
		}

		$data[] = [""];
		$data[] = [""];
		$data[] = ["RINGKASAN SEMUA TAHUN"];
		$data[] = [""];
		$data[] = [
			"Tahun",
			"Total Pendapatan",
			"Total Pengeluaran",
			"Saldo Bersih",
			"Transaksi",
			"Trend",
		];

		$allYearsSummary = [];

		foreach ($yearsData as $year => $yearData) {
			$totalIncome = 0;
			$totalExpense = 0;
			$totalTransactions = 0;

			foreach ($yearData as $monthData) {
				$totalIncome += $monthData->total_income;
				$totalExpense += $monthData->total_expense;
				$totalTransactions += $monthData->transaction_count;
			}

			$allYearsSummary[$year] = [
				"income" => $totalIncome,
				"expense" => $totalExpense,
				"transactions" => $totalTransactions,
				"net" => $totalIncome - $totalExpense,
			];

			// Tentukan trend
			$trend = $this->getYearlyTrend($year, $allYearsSummary);

			$data[] = [
				$year,
				$this->formatCurrency($totalIncome),
				$this->formatCurrency($totalExpense),
				$this->formatCurrency($totalIncome - $totalExpense),
				$totalTransactions,
				$trend,
			];
		}

		// Total semua tahun
		$grandTotalIncome = array_sum(array_column($allYearsSummary, "income"));
		$grandTotalExpense = array_sum(array_column($allYearsSummary, "expense"));
		$grandTotalTransactions = array_sum(
			array_column($allYearsSummary, "transactions")
		);

		$data[] = [
			"GRAND TOTAL",
			$this->formatCurrency($grandTotalIncome),
			$this->formatCurrency($grandTotalExpense),
			$this->formatCurrency($grandTotalIncome - $grandTotalExpense),
			$grandTotalTransactions,
			"",
		];
	}

	private function getYearlyTrend($year, $allYearsSummary)
	{
		$currentYear = $allYearsSummary[$year] ?? null;
		$prevYear = $allYearsSummary[$year - 1] ?? null;

		if (!$currentYear || !$prevYear) {
			return "Baru";
		}

		$growth = 0;
		if ($prevYear["income"] > 0) {
			$growth =
				(($currentYear["income"] - $prevYear["income"]) / $prevYear["income"]) *
				100;
		}

		if ($growth > 20) {
			return "↑↑ Naik Pesat";
		}
		if ($growth > 5) {
			return "↑ Sedikit Naik";
		}
		if ($growth > -5) {
			return "→ Stabil";
		}
		if ($growth > -20) {
			return "↓ Sedikit Turun";
		}
		return "↓↓ Turun Pesat";
	}

	public function headings(): array
	{
		return [];
	}

	public function title(): string
	{
		return "Transaksi";
	}

	public function registerEvents(): array
	{
		return [
			AfterSheet::class => function (AfterSheet $event) {
				$sheet = $event->sheet->getDelegate();

				// Set column widths
				$sheet->getColumnDimension("A")->setWidth(10);
				$sheet->getColumnDimension("B")->setWidth(20);
				$sheet->getColumnDimension("C")->setWidth(20);
				$sheet->getColumnDimension("D")->setWidth(18);
				$sheet->getColumnDimension("E")->setWidth(10);
				$sheet->getColumnDimension("F")->setWidth(15);

				// Apply styling untuk setiap bagian
				$this->styleYearlySections($sheet);
			},
		];
	}

	private function styleYearlySections($sheet)
	{
		$data = $this->array();
		$currentRow = 1;

		// Cari setiap bagian tahun
		foreach ($data as $index => $row) {
			$rowNum = $index + 1;

			if (isset($row[0]) && strpos($row[0], "TRANSAKSI TAHUN") !== false) {
				// Style judul tahun
				$sheet
					->getStyle("A{$rowNum}")
					->getFont()
					->setBold(true)
					->setSize(16)
					->setName("Arial")
					->getColor()
					->setARGB("FF2C3E50");

				$sheet->mergeCells("A{$rowNum}:F{$rowNum}");
				$sheet
					->getStyle("A{$rowNum}")
					->getAlignment()
					->setHorizontal(Alignment::HORIZONTAL_CENTER)
					->setVertical(Alignment::VERTICAL_CENTER);

				$sheet
					->getStyle("A{$rowNum}")
					->getFill()
					->setFillType(Fill::FILL_SOLID)
					->getStartColor()
					->setARGB("FF2C3E50");

				// Style header tabel untuk tahun ini (2 baris setelah judul)
				$headerRow = $rowNum + 2;
				$sheet
					->getStyle("A{$headerRow}:F{$headerRow}")
					->getFont()
					->setBold(true);

				$sheet
					->getStyle("A{$headerRow}:F{$headerRow}")
					->getFill()
					->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
					->getStartColor()
					->setARGB("FFE67E22");

				$sheet
					->getStyle("A{$headerRow}:F{$headerRow}")
					->getFont()
					->getColor()
					->setARGB("FFFFFFFF");

				// Cari baris total untuk tahun ini
				$totalRow = null;
				for ($i = $rowNum + 3; $i <= count($data); $i++) {
					if (
						isset($data[$i - 1][0]) &&
						strpos($data[$i - 1][0], "TOTAL") !== false
					) {
						$totalRow = $i;
						break;
					}
				}

				if ($totalRow) {
					// Apply border untuk tabel tahun ini
					$tableStart = $headerRow;
					$tableEnd = $totalRow;

					$sheet
						->getStyle("A{$tableStart}:F{$tableEnd}")
						->getBorders()
						->getAllBorders()
						->setBorderStyle(Border::BORDER_THIN);

					// Style baris total
					$sheet
						->getStyle("A{$totalRow}:F{$totalRow}")
						->getFont()
						->setBold(true);

					$sheet
						->getStyle("A{$totalRow}:F{$totalRow}")
						->getFill()
						->setFillType(Fill::FILL_SOLID)
						->getStartColor()
						->setARGB("FFFBEEE6");
				}
			}

			// Style ringkasan semua tahun
			if (isset($row[0]) && $row[0] === "RINGKASAN SEMUA TAHUN") {
				$sheet
					->getStyle("A{$rowNum}")
					->getFont()
					->setBold(true)
					->setSize(14)
					->getColor()
					->setARGB("FF2C3E50");

				$sheet->mergeCells("A{$rowNum}:F{$rowNum}");
				$sheet
					->getStyle("A{$rowNum}")
					->getAlignment()
					->setHorizontal(Alignment::HORIZONTAL_CENTER)
					->setVertical(Alignment::VERTICAL_CENTER);

				// Style header ringkasan
				$summaryHeader = $rowNum + 2;
				$sheet
					->getStyle("A{$summaryHeader}:F{$summaryHeader}")
					->getFont()
					->setBold(true);

				$sheet
					->getStyle("A{$summaryHeader}:F{$summaryHeader}")
					->getFill()
					->setFillType(Fill::FILL_SOLID)
					->getStartColor()
					->setARGB("FF27AE60");

				$sheet
					->getStyle("A{$summaryHeader}:F{$summaryHeader}")
					->getFont()
					->getColor()
					->setARGB("FFFFFFFF");

				// Cari baris grand total
				$grandTotalRow = null;
				for ($i = $summaryHeader + 1; $i <= count($data); $i++) {
					if (isset($data[$i - 1][0]) && $data[$i - 1][0] === "GRAND TOTAL") {
						$grandTotalRow = $i;
						break;
					}
				}

				if ($grandTotalRow) {
					// Apply border untuk tabel ringkasan
					$summaryStart = $summaryHeader;
					$summaryEnd = $grandTotalRow;

					$sheet
						->getStyle("A{$summaryStart}:F{$summaryEnd}")
						->getBorders()
						->getAllBorders()
						->setBorderStyle(Border::BORDER_THIN);

					// Style grand total
					$sheet
						->getStyle("A{$grandTotalRow}:F{$grandTotalRow}")
						->getFont()
						->setBold(true);

					$sheet
						->getStyle("A{$grandTotalRow}:F{$grandTotalRow}")
						->getFill()
						->setFillType(Fill::FILL_SOLID)
						->getStartColor()
						->setARGB("FFD5F4E6");
				}
			}

			// Format angka untuk kolom B, C, D, F
			if (isset($row[1]) && is_numeric($row[1])) {
				$sheet
					->getStyle("B{$rowNum}")
					->getNumberFormat()
					->setFormatCode("#,##0");
			}
			if (isset($row[2]) && is_numeric($row[2])) {
				$sheet
					->getStyle("C{$rowNum}")
					->getNumberFormat()
					->setFormatCode("#,##0");
			}
			if (isset($row[3]) && is_numeric($row[3])) {
				$sheet
					->getStyle("D{$rowNum}")
					->getNumberFormat()
					->setFormatCode("#,##0");
			}
			if (isset($row[5]) && is_numeric($row[5])) {
				$sheet
					->getStyle("F{$rowNum}")
					->getNumberFormat()
					->setFormatCode("#,##0");
			}
		}

		// Alignment umum
		$lastRow = count($data);
		$sheet
			->getStyle("A1:F{$lastRow}")
			->getAlignment()
			->setVertical(Alignment::VERTICAL_CENTER);

		$sheet
			->getStyle("B1:F{$lastRow}")
			->getAlignment()
			->setHorizontal(Alignment::HORIZONTAL_RIGHT);

		$sheet
			->getStyle("A1:A{$lastRow}")
			->getAlignment()
			->setHorizontal(Alignment::VERTICAL_LEFT);
	}

	private function getTransactionCount($day)
	{
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
		$amount = is_numeric($amount) ? $amount : 0;

		if ($amount > 1000000) {
			return "Tinggi";
		} elseif ($amount > 500000) {
			return "Sedang";
		} else {
			return "Rendah";
		}
	}
}
