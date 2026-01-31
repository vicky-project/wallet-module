<?php

namespace Modules\Wallet\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;

class CoverSheet implements FromArray, WithTitle, WithEvents
{
	protected $reportData;
	protected $companyName;
	protected $companyAddress;

	public function __construct(array $reportData)
	{
		$this->reportData = $reportData;
		$this->companyName = config(
			"wallet.metadata.company_name",
			"Financial Management System"
		);
		$this->companyAddress = config(
			"wallet.metadata.company_address",
			"Jl. Contoh No. 123, Jakarta, Indonesia"
		);
	}

	public function array(): array
	{
		return [[""]];
	}

	public function title(): string
	{
		return "Cover";
	}

	public function registerEvents(): array
	{
		return [
			AfterSheet::class => function (AfterSheet $event) {
				$sheet = $event->sheet->getDelegate();

				// Setup dasar
				$this->setupBasicLayout($sheet);

				// Header perusahaan
				$this->setupCompanyHeader($sheet);

				// Judul utama dan periode
				$this->setupMainTitle($sheet);

				// Ringkasan keuangan utama
				$this->setupFinancialSummary($sheet);

				// Daftar sheet/laporan
				$this->setupSheetList($sheet);

				// Informasi laporan
				$this->setupReportInfo($sheet);

				// Footer
				$this->setupFooter($sheet);
			},
		];
	}

	private function setupBasicLayout($sheet)
	{
		// Set column widths
		$sheet->getColumnDimension("A")->setWidth(5);
		$sheet->getColumnDimension("B")->setWidth(35);
		$sheet->getColumnDimension("C")->setWidth(20);
		$sheet->getColumnDimension("D")->setWidth(20);
		$sheet->getColumnDimension("E")->setWidth(5);

		// Hide gridlines
		$sheet->setShowGridlines(false);

		// Set page setup
		$sheet
			->getPageSetup()
			->setOrientation(
				\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT
			);
		$sheet
			->getPageSetup()
			->setPaperSize(
				\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4
			);
		$sheet->getPageSetup()->setFitToPage(true);
		$sheet->getPageSetup()->setFitToWidth(1);
	}

	private function setupCompanyHeader($sheet)
	{
		// Background header
		$sheet->mergeCells("B2:D2");
		$sheet->getStyle("B2:D2")->applyFromArray([
			"fill" => [
				"fillType" => Fill::FILL_SOLID,
				"color" => ["rgb" => "2C3E50"],
			],
			"font" => [
				"name" => "Arial",
				"bold" => true,
				"size" => 16,
				"color" => ["rgb" => "FFFFFF"],
			],
			"alignment" => [
				"horizontal" => Alignment::HORIZONTAL_CENTER,
				"vertical" => Alignment::VERTICAL_CENTER,
			],
		]);
		$sheet->setCellValue("B2", $this->companyName);
		$sheet->getRowDimension(2)->setRowHeight(30);

		// Alamat perusahaan
		$sheet->mergeCells("B3:D3");
		$sheet->getStyle("B3:D3")->applyFromArray([
			"fill" => [
				"fillType" => Fill::FILL_SOLID,
				"color" => ["rgb" => "34495E"],
			],
			"font" => [
				"name" => "Arial",
				"size" => 10,
				"color" => ["rgb" => "FFFFFF"],
			],
			"alignment" => [
				"horizontal" => Alignment::HORIZONTAL_CENTER,
				"vertical" => Alignment::VERTICAL_CENTER,
			],
		]);
		$sheet->setCellValue("B3", $this->companyAddress);
		$sheet->getRowDimension(3)->setRowHeight(20);
	}

	private function setupMainTitle($sheet)
	{
		// Spasi
		$sheet->getRowDimension(5)->setRowHeight(15);

		// Judul utama
		$sheet->mergeCells("B6:D6");
		$sheet->getStyle("B6:D6")->applyFromArray([
			"font" => [
				"name" => "Arial",
				"bold" => true,
				"size" => 24,
				"color" => ["rgb" => "2C3E50"],
			],
			"alignment" => [
				"horizontal" => Alignment::HORIZONTAL_CENTER,
				"vertical" => Alignment::VERTICAL_CENTER,
			],
		]);
		$sheet->setCellValue("B6", "LAPORAN KEUANGAN");
		$sheet->getRowDimension(6)->setRowHeight(35);

		// Subtitle
		$sheet->mergeCells("B7:D7");
		$sheet->getStyle("B7:D7")->applyFromArray([
			"font" => [
				"name" => "Arial",
				"italic" => true,
				"size" => 12,
				"color" => ["rgb" => "7F8C8D"],
			],
			"alignment" => [
				"horizontal" => Alignment::HORIZONTAL_CENTER,
				"vertical" => Alignment::VERTICAL_CENTER,
			],
		]);
		$sheet->setCellValue("B7", "Analisis dan Ringkasan Keuangan");
		$sheet->getRowDimension(7)->setRowHeight(20);

		// Garis dekoratif
		$sheet->mergeCells("B8:D8");
		$sheet->getStyle("B8:D8")->applyFromArray([
			"borders" => [
				"bottom" => [
					"borderStyle" => Border::BORDER_MEDIUM,
					"color" => ["rgb" => "3498DB"],
				],
			],
		]);
		$sheet->getRowDimension(8)->setRowHeight(10);

		// Spasi
		$sheet->getRowDimension(9)->setRowHeight(15);
	}

	private function setupFinancialSummary($sheet)
	{
		$summary = $this->reportData["report_data"]["financial_summary"] ?? [];
		$income = $summary["income_number"] ?? 0;
		$expense = $summary["expense_number"] ?? 0;
		$netFlow = $summary["net_flow"] ?? 0;

		// Container ringkasan
		$startRow = 10;
		$sheet->mergeCells("B{$startRow}:D" . ($startRow + 4));
		$sheet->getStyle("B{$startRow}:D" . ($startRow + 4))->applyFromArray([
			"fill" => [
				"fillType" => Fill::FILL_SOLID,
				"color" => ["rgb" => "F8F9FA"],
			],
			"borders" => [
				"allBorders" => [
					"borderStyle" => Border::BORDER_THIN,
					"color" => ["rgb" => "DEE2E6"],
				],
			],
		]);

		// Judul ringkasan
		$sheet->mergeCells("B{$startRow}:D{$startRow}");
		$sheet->getStyle("B{$startRow}:D{$startRow}")->applyFromArray([
			"fill" => [
				"fillType" => Fill::FILL_SOLID,
				"color" => ["rgb" => "E9ECEF"],
			],
			"font" => [
				"name" => "Arial",
				"bold" => true,
				"size" => 12,
				"color" => ["rgb" => "2C3E50"],
			],
			"alignment" => [
				"horizontal" => Alignment::HORIZONTAL_CENTER,
				"vertical" => Alignment::VERTICAL_CENTER,
			],
		]);
		$sheet->setCellValue("B{$startRow}", "RINGKASAN KEUANGAN");
		$sheet->getRowDimension($startRow)->setRowHeight(25);

		// Data ringkasan
		$data = [
			["Total Pendapatan", $this->formatCurrency($income), "00A651"],
			["Total Pengeluaran", $this->formatCurrency($expense), "E74C3C"],
			[
				"Saldo Bersih",
				$this->formatCurrency($netFlow),
				$netFlow >= 0 ? "27AE60" : "E74C3C",
			],
		];

		$currentRow = $startRow + 1;
		foreach ($data as $item) {
			$sheet->setCellValue("B{$currentRow}", $item[0]);
			$sheet->setCellValue("C{$currentRow}", $item[1]);

			$sheet->getStyle("B{$currentRow}")->applyFromArray([
				"font" => [
					"name" => "Arial",
					"bold" => true,
					"size" => 11,
				],
				"alignment" => [
					"horizontal" => Alignment::HORIZONTAL_LEFT,
					"vertical" => Alignment::VERTICAL_CENTER,
				],
			]);

			$sheet->getStyle("C{$currentRow}")->applyFromArray([
				"font" => [
					"name" => "Arial",
					"bold" => true,
					"size" => 11,
					"color" => ["rgb" => $item[2]],
				],
				"alignment" => [
					"horizontal" => Alignment::HORIZONTAL_RIGHT,
					"vertical" => Alignment::VERTICAL_CENTER,
				],
				"numberFormat" => [
					"formatCode" => "#,##0",
				],
			]);

			$sheet->getRowDimension($currentRow)->setRowHeight(22);
			$currentRow++;
		}

		// Spasi
		$sheet->getRowDimension($currentRow)->setRowHeight(15);
	}

	private function setupSheetList($sheet)
	{
		$startRow = 18;

		// Container daftar sheet
		$sheet->mergeCells("B{$startRow}:D" . ($startRow + 8));
		$sheet->getStyle("B{$startRow}:D" . ($startRow + 8))->applyFromArray([
			"fill" => [
				"fillType" => Fill::FILL_SOLID,
				"color" => ["rgb" => "FFFFFF"],
			],
			"borders" => [
				"allBorders" => [
					"borderStyle" => Border::BORDER_THIN,
					"color" => ["rgb" => "DEE2E6"],
				],
			],
		]);

		// Judul daftar sheet
		$sheet->mergeCells("B{$startRow}:D{$startRow}");
		$sheet->getStyle("B{$startRow}:D{$startRow}")->applyFromArray([
			"fill" => [
				"fillType" => Fill::FILL_SOLID,
				"color" => ["rgb" => "3498DB"],
			],
			"font" => [
				"name" => "Arial",
				"bold" => true,
				"size" => 12,
				"color" => ["rgb" => "FFFFFF"],
			],
			"alignment" => [
				"horizontal" => Alignment::HORIZONTAL_CENTER,
				"vertical" => Alignment::VERTICAL_CENTER,
			],
		]);
		$sheet->setCellValue("B{$startRow}", "DAFTAR LAPORAN");
		$sheet->getRowDimension($startRow)->setRowHeight(25);

		// Daftar sheet yang tersedia
		$sheets = [
			["Ringkasan", "Ringkasan keuangan dan analisis"],
			["Transaksi", "Detail transaksi per bulan"],
			["Kategori", "Analisis pengeluaran per kategori"],
			["Anggaran", "Analisis anggaran vs realisasi"],
			["Rekap", "Ringkasan per akun dan periode"],
		];

		$currentRow = $startRow + 1;
		foreach ($sheets as $index => $sheetItem) {
			$sheet->setCellValue("B{$currentRow}", $sheetItem[0]);
			$sheet->setCellValue("C{$currentRow}", $sheetItem[1]);
			$sheet->setCellValue("D{$currentRow}", "Hal. " . ($index + 2));

			$sheet->getStyle("B{$currentRow}")->applyFromArray([
				"font" => [
					"name" => "Arial",
					"bold" => true,
					"size" => 11,
					"color" => ["rgb" => "2C3E50"],
				],
				"alignment" => [
					"horizontal" => Alignment::HORIZONTAL_LEFT,
					"vertical" => Alignment::VERTICAL_CENTER,
				],
			]);

			$sheet->getStyle("C{$currentRow}")->applyFromArray([
				"font" => [
					"name" => "Arial",
					"size" => 10,
					"color" => ["rgb" => "7F8C8D"],
				],
				"alignment" => [
					"horizontal" => Alignment::HORIZONTAL_LEFT,
					"vertical" => Alignment::VERTICAL_CENTER,
				],
			]);

			$sheet->getStyle("D{$currentRow}")->applyFromArray([
				"font" => [
					"name" => "Arial",
					"italic" => true,
					"size" => 10,
					"color" => ["rgb" => "95A5A6"],
				],
				"alignment" => [
					"horizontal" => Alignment::HORIZONTAL_RIGHT,
					"vertical" => Alignment::VERTICAL_CENTER,
				],
			]);

			// Alternating row colors
			if ($index % 2 == 0) {
				$sheet->getStyle("B{$currentRow}:D{$currentRow}")->applyFromArray([
					"fill" => [
						"fillType" => Fill::FILL_SOLID,
						"color" => ["rgb" => "F8F9FA"],
					],
				]);
			}

			$sheet->getRowDimension($currentRow)->setRowHeight(20);
			$currentRow++;
		}

		// Spasi
		$sheet->getRowDimension($currentRow)->setRowHeight(10);
	}

	private function setupReportInfo($sheet)
	{
		$startRow = 28;

		// Container informasi
		$sheet->mergeCells("B{$startRow}:D" . ($startRow + 5));
		$sheet->getStyle("B{$startRow}:D" . ($startRow + 5))->applyFromArray([
			"fill" => [
				"fillType" => Fill::FILL_SOLID,
				"color" => ["rgb" => "FFFFFF"],
			],
			"borders" => [
				"allBorders" => [
					"borderStyle" => Border::BORDER_THIN,
					"color" => ["rgb" => "DEE2E6"],
				],
			],
		]);

		// Judul informasi
		$sheet->mergeCells("B{$startRow}:D{$startRow}");
		$sheet->getStyle("B{$startRow}:D{$startRow}")->applyFromArray([
			"fill" => [
				"fillType" => Fill::FILL_SOLID,
				"color" => ["rgb" => "2C3E50"],
			],
			"font" => [
				"name" => "Arial",
				"bold" => true,
				"size" => 11,
				"color" => ["rgb" => "FFFFFF"],
			],
			"alignment" => [
				"horizontal" => Alignment::HORIZONTAL_CENTER,
				"vertical" => Alignment::VERTICAL_CENTER,
			],
		]);
		$sheet->setCellValue("B{$startRow}", "INFORMASI DOKUMEN");
		$sheet->getRowDimension($startRow)->setRowHeight(22);

		// Data informasi
		$period = $this->formatPeriod($this->reportData["period"] ?? []);
		$exportDate = isset($this->reportData["exported_at"])
			? date("d F Y H:i", strtotime($this->reportData["exported_at"]))
			: date("d F Y H:i");

		$infoData = [
			["Periode Laporan:", $period],
			["Tanggal Ekspor:", $exportDate],
			["Mata Uang:", $this->reportData["currency"] ?? "IDR"],
			["Format File:", "Microsoft Excel (.xlsx)"],
			["Status Dokumen:", "Dokumen Resmi"],
			["Konfidensial:", "Tinggi"],
		];

		$currentRow = $startRow + 1;
		foreach ($infoData as $info) {
			$sheet->setCellValue("B{$currentRow}", $info[0]);
			$sheet->setCellValue("C{$currentRow}", $info[1]);

			$sheet->getStyle("B{$currentRow}")->applyFromArray([
				"font" => [
					"name" => "Arial",
					"bold" => true,
					"size" => 10,
					"color" => ["rgb" => "2C3E50"],
				],
				"alignment" => [
					"horizontal" => Alignment::HORIZONTAL_LEFT,
					"vertical" => Alignment::VERTICAL_CENTER,
				],
			]);

			$sheet->getStyle("C{$currentRow}")->applyFromArray([
				"font" => [
					"name" => "Arial",
					"size" => 10,
					"color" => ["rgb" => "34495E"],
				],
				"alignment" => [
					"horizontal" => Alignment::HORIZONTAL_LEFT,
					"vertical" => Alignment::VERTICAL_CENTER,
				],
			]);

			$sheet->getRowDimension($currentRow)->setRowHeight(18);
			$currentRow++;
		}

		// Spasi
		$sheet->getRowDimension($currentRow)->setRowHeight(15);
	}

	private function setupFooter($sheet)
	{
		$footerRow = 36;

		// Garis pemisah
		$sheet->mergeCells("B{$footerRow}:D{$footerRow}");
		$sheet->getStyle("B{$footerRow}:D{$footerRow}")->applyFromArray([
			"borders" => [
				"top" => [
					"borderStyle" => Border::BORDER_MEDIUM,
					"color" => ["rgb" => "3498DB"],
				],
			],
		]);
		$sheet->getRowDimension($footerRow)->setRowHeight(10);

		// Copyright
		$footerRow++;
		$sheet->mergeCells("B{$footerRow}:D{$footerRow}");
		$currentYear = date("Y");
		$sheet->setCellValue(
			"B{$footerRow}",
			"© {$currentYear} {$this->companyName} - Hak Cipta Dilindungi"
		);
		$sheet->getStyle("B{$footerRow}")->applyFromArray([
			"font" => [
				"name" => "Arial",
				"size" => 9,
				"color" => ["rgb" => "7F8C8D"],
			],
			"alignment" => [
				"horizontal" => Alignment::HORIZONTAL_CENTER,
				"vertical" => Alignment::VERTICAL_CENTER,
			],
		]);
		$sheet->getRowDimension($footerRow)->setRowHeight(18);

		// Generated by
		$footerRow++;
		$sheet->mergeCells("B{$footerRow}:D{$footerRow}");
		$sheet->setCellValue(
			"B{$footerRow}",
			"Dibuat oleh: Financial Management System v" .
				($this->reportData["version"] ?? "1.0")
		);
		$sheet->getStyle("B{$footerRow}")->applyFromArray([
			"font" => [
				"name" => "Arial",
				"size" => 8,
				"color" => ["rgb" => "95A5A6"],
			],
			"alignment" => [
				"horizontal" => Alignment::HORIZONTAL_CENTER,
				"vertical" => Alignment::VERTICAL_CENTER,
			],
		]);
		$sheet->getRowDimension($footerRow)->setRowHeight(16);
	}

	private function formatCurrency($value)
	{
		if (!is_numeric($value)) {
			return "0";
		}
		return $value / 100;
	}

	private function formatPeriod(array $period): string
	{
		if (!isset($period["start_date"]) || !isset($period["end_date"])) {
			return "Semua Periode";
		}

		$start = date("d M Y", strtotime($period["start_date"]));
		$end = date("d M Y", strtotime($period["end_date"]));

		return "{$start} - {$end}";
	}
}
