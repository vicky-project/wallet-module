<?php

namespace Modules\Wallet\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class CoverSheet implements FromArray, WithTitle, WithEvents, WithDrawings
{
	protected $reportData;

	public function __construct(array $reportData)
	{
		$this->reportData = $reportData;
	}

	public function array(): array
	{
		// Data untuk cover sheet
		$data = [
			[""],
			[""],
			[""],
			["LAPORAN KEUANGAN"],
			[""],
			[""],
			["Periode:", $this->reportData["period"] ?? ""],
			[
				"Tanggal Ekspor:",
				$this->reportData["exported_at"] ?? now()->format("Y-m-d H:i:s"),
			],
			["Mata Uang:", $this->reportData["currency"] ?? "IDR"],
			[""],
			[""],
			["DAFTAR ISI"],
			[""],
			["1. Ringkasan Eksekutif"],
			["2. Trend Pendapatan vs Pengeluaran"],
			["3. Analisis Pengeluaran per Kategori"],
			["4. Analisis Anggaran vs Realisasi"],
			["5. Distribusi Saldo Akun"],
			["6. Aktivitas Transaksi"],
			[""],
			[""],
			["CATATAN PENTING"],
			["• Laporan ini dibuat secara otomatis oleh sistem"],
			["• Data bersumber dari transaksi yang tercatat"],
			["• Chart tersedia dalam versi Excel (XLSX)"],
			["• Untuk pertanyaan, hubungi administrator sistem"],
		];

		return $data;
	}

	public function title(): string
	{
		return "Cover";
	}

	public function drawings()
	{
		// Pastikan file logo ada sebelum membuat drawing
		$logoPath = public_path("images/logo.png");

		// Jika logo tidak ada, jangan return drawing
		if (!file_exists($logoPath)) {
			return [];
		}

		$drawing = new Drawing();
		$drawing->setName("Logo");
		$drawing->setDescription("Logo Perusahaan");
		$drawing->setPath($logoPath);
		$drawing->setHeight(60); // Ukuran lebih kecil
		$drawing->setCoordinates("B2"); // Posisi di kolom B row 2

		return [$drawing];
	}

	public function registerEvents(): array
	{
		return [
			AfterSheet::class => function (AfterSheet $event) {
				$sheet = $event->sheet->getDelegate();

				// ============ SET COLUMN WIDTHS ============
				$sheet->getColumnDimension("A")->setWidth(5);
				$sheet->getColumnDimension("B")->setWidth(30);
				$sheet->getColumnDimension("C")->setWidth(40);
				$sheet->getColumnDimension("D")->setWidth(10);
				$sheet->getColumnDimension("E")->setWidth(10);
				$sheet->getColumnDimension("F")->setWidth(10);

				// ============ JUDUL UTAMA ============
				// Title di row 4 (karena ada 3 baris kosong di awal)
				$titleRow = 4;
				$sheet->mergeCells("B{$titleRow}:F{$titleRow}");
				$sheet
					->getStyle("B{$titleRow}")
					->getFont()
					->setBold(true)
					->setSize(24)
					->getColor()
					->setARGB("FF2C3E50");

				$sheet
					->getStyle("B{$titleRow}")
					->getAlignment()
					->setHorizontal("center")
					->setVertical("center");

				// ============ INFORMASI PERIODE ============
				$infoStartRow = 7;
				$sheet
					->getStyle("B{$infoStartRow}:C" . ($infoStartRow + 2))
					->getBorders()
					->getAllBorders()
					->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

				$sheet
					->getStyle("B{$infoStartRow}:C" . ($infoStartRow + 2))
					->getFill()
					->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
					->getStartColor()
					->setARGB("FFF8F9F9");

				// Format label informasi
				for ($row = $infoStartRow; $row <= $infoStartRow + 2; $row++) {
					$sheet
						->getStyle("B{$row}")
						->getFont()
						->setBold(true)
						->getColor()
						->setARGB("FF2C3E50");
				}

				// ============ DAFTAR ISI ============
				$daftarIsiRow = 12;
				$sheet
					->getStyle("B{$daftarIsiRow}")
					->getFont()
					->setBold(true)
					->setSize(14)
					->getColor()
					->setARGB("FF2C3E50");

				$sheet
					->getStyle("B{$daftarIsiRow}")
					->getFill()
					->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
					->getStartColor()
					->setARGB("FFF4F6F6");

				$sheet->mergeCells("B{$daftarIsiRow}:F{$daftarIsiRow}");

				// ============ CATATAN ============
				$catatanRow = 22;
				$sheet
					->getStyle("B{$catatanRow}")
					->getFont()
					->setBold(true)
					->setSize(12)
					->getColor()
					->setARGB("FF34495E");

				$sheet->mergeCells("B{$catatanRow}:F{$catatanRow}");

				// ============ FOOTER ============
				$lastRow = $sheet->getHighestRow();
				$footerRow = $lastRow + 2;

				$sheet->mergeCells("B{$footerRow}:F{$footerRow}");
				$sheet->setCellValue(
					"B{$footerRow}",
					"© " . date("Y") . " Sistem Keuangan - Confidential"
				);

				$sheet
					->getStyle("B{$footerRow}")
					->getFont()
					->setItalic(true)
					->setSize(9)
					->getColor()
					->setARGB("FF7F8C8D");

				$sheet
					->getStyle("B{$footerRow}")
					->getAlignment()
					->setHorizontal("center");

				// ============ SET ROW HEIGHTS ============
				$sheet->getRowDimension($titleRow)->setRowHeight(40);
				$sheet->getRowDimension($daftarIsiRow)->setRowHeight(25);
				$sheet->getRowDimension($catatanRow)->setRowHeight(25);

				// Row untuk informasi periode
				for ($row = $infoStartRow; $row <= $infoStartRow + 2; $row++) {
					$sheet->getRowDimension($row)->setRowHeight(25);
				}

				// ============ HIDE GRIDLINES ============
				$sheet->setShowGridlines(false);
			},
		];
	}
}
