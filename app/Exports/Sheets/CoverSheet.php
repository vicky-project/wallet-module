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
			"Financial System"
		);
		$this->companyAddress = config(
			"wallet.metadata.company_address",
			"Your Company Address"
		);
	}

	public function array(): array
	{
		// Format periode yang lebih baik
		$period = $this->formatPeriod($this->reportData["period"] ?? []);
		$exportDate = isset($this->reportData["exported_at"])
			? date("d F Y H:i", strtotime($this->reportData["exported_at"]))
			: date("d F Y H:i");

		// Data untuk cover sheet dengan layout yang lebih terstruktur
		return [[""]];
		$data = [
			[""],
			[""],
			[""],
			[""], // Row untuk logo dan judul
			[""],
			[""],
			[""],
			[""],
			[""],
			[""],
			[""],
			[""],
			[""],
			[""],
			[""],
			[""],
			[""],
			[""],
			[""],
			[""],
			[""],
			[""],
			[""],
			[""],
			[""],
			[""],
			[""],
			[""],
			[""],
			[""],
			[""],
			[""],
			[""],
			[""],
			[""],
			[""],
			[""],
			[""],
		];

		// Judul utama akan ditambahkan via styling, bukan di array
		return $data;
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

				// ============ SET LAYOUT DASAR ============
				$this->setupBasicLayout($sheet);

				// ============ HEADER SECTION ============
				$this->setupHeader($sheet);

				// ============ JUDUL UTAMA ============
				$this->setupMainTitle($sheet);

				// ============ INFORMASI LAPORAN ============
				$this->setupReportInfo($sheet);

				// ============ DAFTAR ISI ============
				$this->setupTableOfContents($sheet);

				// ============ CATATAN ============
				$this->setupNotes($sheet);

				// ============ FOOTER ============
				$this->setupFooter($sheet);

				// ============ FINAL TOUCHES ============
				$this->finalStyling($sheet);
			},
		];
	}

	private function setupBasicLayout($sheet)
	{
		// Set column widths
		$sheet->getColumnDimension("A")->setWidth(5);
		$sheet->getColumnDimension("B")->setWidth(30);
		$sheet->getColumnDimension("C")->setWidth(30);
		$sheet->getColumnDimension("D")->setWidth(30);
		$sheet->getColumnDimension("E")->setWidth(5);

		// Set default font for entire sheet
		$sheet
			->getParent()
			->getDefaultStyle()
			->getFont()
			->setName("Calibri")
			->setSize(11);

		// Hide gridlines for cleaner look
		$sheet->setShowGridlines(false);

		// Set print area
		$sheet->getPageSetup()->setPrintArea("A1:E40");
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
		$sheet->getPageSetup()->setFitToHeight(0);
	}

	private function setupHeader($sheet)
	{
		// Nama perusahaan (atas)
		$sheet->mergeCells("B1:D1");
		$sheet->setCellValue("B1", strtoupper($this->companyName));
		$sheet->getStyle("B1")->applyFromArray([
			"font" => [
				"name" => "Calibri",
				"bold" => true,
				"size" => 16,
				"color" => ["rgb" => "2C3E50"],
			],
			"alignment" => [
				"horizontal" => Alignment::HORIZONTAL_CENTER,
				"vertical" => Alignment::VERTICAL_CENTER,
			],
		]);

		// Alamat perusahaan
		$sheet->mergeCells("B2:D2");
		$sheet->setCellValue("B2", $this->companyAddress);
		$sheet->getStyle("B2")->applyFromArray([
			"font" => [
				"name" => "Calibri",
				"size" => 10,
				"color" => ["rgb" => "7F8C8D"],
			],
			"alignment" => [
				"horizontal" => Alignment::HORIZONTAL_CENTER,
				"vertical" => Alignment::VERTICAL_CENTER,
			],
		]);

		// Garis pemisah
		$sheet->mergeCells("B3:D3");
		$sheet->getStyle("B3:E3")->applyFromArray([
			"borders" => [
				"bottom" => [
					"borderStyle" => Border::BORDER_MEDIUM,
					"color" => ["rgb" => "3498DB"],
				],
			],
		]);
	}

	private function setupMainTitle($sheet)
	{
		// Title container background
		$sheet->mergeCells("B6:D6");
		$sheet->getStyle("B6:D6")->applyFromArray([
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

		// Main title
		$sheet->mergeCells("B7:D7");
		$sheet->setCellValue("B7", "LAPORAN KEUANGAN");
		$sheet->getStyle("B7")->applyFromArray([
			"font" => [
				"name" => "Calibri",
				"bold" => true,
				"size" => 24,
				"color" => ["rgb" => "2C3E50"],
			],
			"alignment" => [
				"horizontal" => Alignment::HORIZONTAL_CENTER,
				"vertical" => Alignment::VERTICAL_CENTER,
			],
		]);

		// Subtitle
		$sheet->mergeCells("B8:D8");
		$sheet->setCellValue("B8", "Analisis dan Ringkasan Keuangan");
		$sheet->getStyle("B8")->applyFromArray([
			"font" => [
				"name" => "Calibri",
				"italic" => true,
				"size" => 12,
				"color" => ["rgb" => "7F8C8D"],
			],
			"alignment" => [
				"horizontal" => Alignment::HORIZONTAL_CENTER,
				"vertical" => Alignment::VERTICAL_CENTER,
			],
		]);

		// Decorative line under title
		$sheet->mergeCells("B9:D9");
		$sheet->getStyle("B9:D9")->applyFromArray([
			"borders" => [
				"bottom" => [
					"borderStyle" => Border::BORDER_MEDIUM,
					"color" => ["rgb" => "3498DB"],
				],
			],
		]);
	}

	private function setupReportInfo($sheet)
	{
		$period = $this->formatPeriod($this->reportData["period"] ?? []);
		$exportDate = isset($this->reportData["exported_at"])
			? date("d F Y H:i", strtotime($this->reportData["exported_at"]))
			: date("d F Y H:i");

		// Info box container
		$infoStartRow = 11;
		$sheet->mergeCells("B{$infoStartRow}:D" . ($infoStartRow + 4));
		$sheet
			->getStyle("B{$infoStartRow}:D" . ($infoStartRow + 4))
			->applyFromArray([
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

		// Info title
		$sheet->mergeCells("B{$infoStartRow}:D{$infoStartRow}");
		$sheet->setCellValue("B{$infoStartRow}", "INFORMASI LAPORAN");
		$sheet->getStyle("B{$infoStartRow}")->applyFromArray([
			"font" => [
				"name" => "Calibri",
				"bold" => true,
				"size" => 12,
				"color" => ["rgb" => "2C3E50"],
			],
			"alignment" => [
				"horizontal" => Alignment::HORIZONTAL_CENTER,
				"vertical" => Alignment::VERTICAL_CENTER,
			],
			"fill" => [
				"fillType" => Fill::FILL_SOLID,
				"color" => ["rgb" => "E9ECEF"],
			],
		]);

		// Info details
		$infoRow = $infoStartRow + 1;
		$sheet->setCellValue("B{$infoRow}", "Periode:");
		$sheet->setCellValue("C{$infoRow}", $period);
		$sheet
			->getStyle("B{$infoRow}")
			->getFont()
			->setBold(true);

		$infoRow++;
		$sheet->setCellValue("B{$infoRow}", "Tanggal Ekspor:");
		$sheet->setCellValue("C{$infoRow}", $exportDate);
		$sheet
			->getStyle("B{$infoRow}")
			->getFont()
			->setBold(true);

		$infoRow++;
		$sheet->setCellValue("B{$infoRow}", "Mata Uang:");
		$sheet->setCellValue("C{$infoRow}", $this->reportData["currency"] ?? "IDR");
		$sheet
			->getStyle("B{$infoRow}")
			->getFont()
			->setBold(true);

		// Format untuk nilai
		$sheet
			->getStyle("C" . ($infoStartRow + 1) . ":C" . ($infoStartRow + 3))
			->getFont()
			->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color("FF34495E"));
	}

	private function setupTableOfContents($sheet)
	{
		$tocStartRow = 17;

		// TOC container
		$sheet->mergeCells("B{$tocStartRow}:D" . ($tocStartRow + 8));
		$sheet->getStyle("B{$tocStartRow}:D" . ($tocStartRow + 8))->applyFromArray([
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

		// TOC title
		$sheet->mergeCells("B{$tocStartRow}:D{$tocStartRow}");
		$sheet->setCellValue("B{$tocStartRow}", "DAFTAR ISI");
		$sheet->getStyle("B{$tocStartRow}")->applyFromArray([
			"font" => [
				"name" => "Calibri",
				"bold" => true,
				"size" => 14,
				"color" => ["rgb" => "2C3E50"],
			],
			"alignment" => [
				"horizontal" => Alignment::HORIZONTAL_CENTER,
				"vertical" => Alignment::VERTICAL_CENTER,
			],
			"fill" => [
				"fillType" => Fill::FILL_SOLID,
				"color" => ["rgb" => "E9ECEF"],
			],
		]);

		// TOC items
		$sections = [
			"1. RINGKASAN EKSEKUTIF",
			"2. TREND PENDAPATAN vs PENGELUARAN",
			"3. ANALISIS PENGELUARAN PER KATEGORI",
			"4. ANALISIS ANGGARAN vs REALISASI",
			"5. DISTRIBUSI SALDO AKUN",
			"6. AKTIVITAS TRANSAKSI",
		];

		$row = $tocStartRow + 1;
		foreach ($sections as $section) {
			$sheet->setCellValue("B{$row}", $section);
			$sheet
				->getStyle("B{$row}")
				->getFont()
				->setSize(11);
			$sheet->getRowDimension($row)->setRowHeight(22);
			$row++;
		}

		// Add page numbers
		$row = $tocStartRow + 1;
		foreach ($sections as $index => $section) {
			$sheet->setCellValue("D{$row}", "Hal. " . ($index + 2));
			$sheet
				->getStyle("D{$row}")
				->getFont()
				->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color("FF7F8C8D"));
			$sheet
				->getStyle("D{$row}")
				->getAlignment()
				->setHorizontal(Alignment::HORIZONTAL_RIGHT);
			$row++;
		}
	}

	private function setupNotes($sheet)
	{
		$notesStartRow = 26;

		// Notes container
		$sheet->mergeCells("B{$notesStartRow}:D" . ($notesStartRow + 5));
		$sheet
			->getStyle("B{$notesStartRow}:D" . ($notesStartRow + 5))
			->applyFromArray([
				"fill" => [
					"fillType" => Fill::FILL_SOLID,
					"color" => ["rgb" => "FFF9E6"],
				],
				"borders" => [
					"allBorders" => [
						"borderStyle" => Border::BORDER_THIN,
						"color" => ["rgb" => "FFEAA7"],
					],
				],
			]);

		// Notes title
		$sheet->mergeCells("B{$notesStartRow}:D{$notesStartRow}");
		$sheet->setCellValue("B{$notesStartRow}", "CATATAN PENTING");
		$sheet->getStyle("B{$notesStartRow}")->applyFromArray([
			"font" => [
				"name" => "Calibri",
				"bold" => true,
				"size" => 12,
				"color" => ["rgb" => "E67E22"],
			],
			"alignment" => [
				"horizontal" => Alignment::HORIZONTAL_CENTER,
				"vertical" => Alignment::VERTICAL_CENTER,
			],
		]);

		// Notes items
		$notes = [
			"• Laporan ini dibuat secara otomatis oleh sistem",
			"• Data bersumber dari transaksi yang tercatat",
			"• Chart interaktif tersedia dalam versi Excel (XLSX)",
			"• Untuk pertanyaan, hubungi administrator sistem",
			"• Dokumen ini bersifat rahasia dan terbatas",
		];

		$row = $notesStartRow + 1;
		foreach ($notes as $note) {
			$sheet->setCellValue("B{$row}", $note);
			$sheet
				->getStyle("B{$row}")
				->getFont()
				->setSize(10);
			$row++;
		}
	}

	private function setupFooter($sheet)
	{
		$footerRow = 33;

		// Decorative line
		$sheet->mergeCells("B{$footerRow}:D{$footerRow}");
		$sheet->getStyle("B{$footerRow}:D{$footerRow}")->applyFromArray([
			"borders" => [
				"top" => [
					"borderStyle" => Border::BORDER_MEDIUM,
					"color" => ["rgb" => "3498DB"],
				],
			],
		]);

		// Footer text
		$footerRow++;
		$sheet->mergeCells("B{$footerRow}:D{$footerRow}");
		$currentYear = date("Y");
		$sheet->setCellValue(
			"B{$footerRow}",
			"© {$currentYear} {$this->companyName} • Dokumen Rahasia"
		);
		$sheet->getStyle("B{$footerRow}")->applyFromArray([
			"font" => [
				"name" => "Calibri",
				"italic" => true,
				"size" => 9,
				"color" => ["rgb" => "7F8C8D"],
			],
			"alignment" => [
				"horizontal" => Alignment::HORIZONTAL_CENTER,
				"vertical" => Alignment::VERTICAL_CENTER,
			],
		]);

		// Generated by
		$footerRow++;
		$sheet->mergeCells("B{$footerRow}:D{$footerRow}");
		$sheet->setCellValue(
			"B{$footerRow}",
			"Generated by Vickyserver Financial System"
		);
		$sheet->getStyle("B{$footerRow}")->applyFromArray([
			"font" => [
				"name" => "Calibri",
				"size" => 8,
				"color" => ["rgb" => "95A5A6"],
			],
			"alignment" => [
				"horizontal" => Alignment::HORIZONTAL_CENTER,
				"vertical" => Alignment::VERTICAL_CENTER,
			],
		]);
	}

	private function finalStyling($sheet)
	{
		// Center all content horizontally
		$sheet
			->getStyle("A1:E40")
			->getAlignment()
			->setHorizontal(Alignment::HORIZONTAL_CENTER);

		// Set row heights for better spacing
		$sheet->getRowDimension(1)->setRowHeight(25);
		$sheet->getRowDimension(2)->setRowHeight(20);
		$sheet->getRowDimension(3)->setRowHeight(5);
		$sheet->getRowDimension(6)->setRowHeight(10);
		$sheet->getRowDimension(7)->setRowHeight(35);
		$sheet->getRowDimension(8)->setRowHeight(20);
		$sheet->getRowDimension(9)->setRowHeight(5);
		$sheet->getRowDimension(10)->setRowHeight(15);

		// Set page margins
		$sheet->getPageMargins()->setTop(0.75);
		$sheet->getPageMargins()->setRight(0.75);
		$sheet->getPageMargins()->setLeft(0.75);
		$sheet->getPageMargins()->setBottom(0.75);
		$sheet->getPageMargins()->setHeader(0.3);
		$sheet->getPageMargins()->setFooter(0.3);

		// Set print titles
		$sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 3);
	}

	private function formatPeriod(array $period): string
	{
		if (!isset($period["start_date"]) || !isset($period["end_date"])) {
			return "Semua Periode";
		}

		$start = date("d F Y", strtotime($period["start_date"]));
		$end = date("d F Y", strtotime($period["end_date"]));

		return "{$start} - {$end}";
	}

	// Helper method to get active sheet (for watermark)
	private function getActiveSheet()
	{
		// This method is used for watermark drawing
		return null; // Will be set by Laravel Excel
	}
}
