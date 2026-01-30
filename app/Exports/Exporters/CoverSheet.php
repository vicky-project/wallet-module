<?php

namespace Modules\Wallet\Exports\Exporters;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class CoverSheet implements FromArray, WithTitle, WithEvents, WithDrawings
{
	protected $reportData;

	public function __construct(array $reportData)
	{
		$this->reportData = $reportData;
	}

	public function array(): array
	{
		return [
			["LAPORAN KEUANGAN"],
			[""],
			["Periode:", $this->reportData["period"] ?? ""],
			[
				"Tanggal Ekspor:",
				$this->reportData["exported_at"] ?? now()->format("Y-m-d H:i:s"),
			],
			["Mata Uang:", $this->reportData["currency"] ?? "IDR"],
			[""],
			["Daftar Isi:"],
			["1. Ringkasan Eksekutif"],
			["2. Trend Pendapatan vs Pengeluaran"],
			["3. Analisis Pengeluaran per Kategori"],
			["4. Analisis Anggaran vs Realisasi"],
			["5. Distribusi Saldo Akun"],
			["6. Aktivitas Transaksi"],
			[""],
			["Catatan:"],
			["• Laporan ini dibuat secara otomatis oleh sistem"],
			["• Data bersumber dari transaksi yang tercatat"],
			["• Chart tersedia dalam versi Excel (XLSX)"],
			["• Untuk pertanyaan, hubungi administrator"],
		];
	}

	public function title(): string
	{
		return "Cover";
	}

	public function drawings()
	{
		$drawing = new Drawing();
		$drawing->setName("Logo");
		$drawing->setDescription("Logo Perusahaan");
		$drawing->setPath(public_path("images/logo.png")); // Sesuaikan dengan path logo Anda
		$drawing->setHeight(80);
		$drawing->setCoordinates("B2");

		return [$drawing];
	}

	public function registerEvents(): array
	{
		return [
			AfterSheet::class => function (AfterSheet $event) {
				$sheet = $event->sheet->getDelegate();

				// Set all column widths
				$sheet->getColumnDimension("A")->setWidth(30);
				$sheet->getColumnDimension("B")->setWidth(40);

				// Merge cells for title
				$sheet->mergeCells("B1:F1");
				$sheet->setCellValue("B1", "LAPORAN KEUANGAN");
				$sheet->getStyle("B1")->applyFromArray([
					"font" => [
						"bold" => true,
						"size" => 24,
						"color" => ["rgb" => "2C3E50"],
					],
					"alignment" => [
						"horizontal" => Alignment::HORIZONTAL_CENTER,
						"vertical" => Alignment::VERTICAL_CENTER,
					],
				]);

				// Style for section headers
				$sheet->getStyle("A7")->applyFromArray([
					"font" => [
						"bold" => true,
						"size" => 14,
						"color" => ["rgb" => "2C3E50"],
					],
					"fill" => [
						"fillType" => Fill::FILL_SOLID,
						"color" => ["rgb" => "F4F6F6"],
					],
				]);

				// Style for notes
				$sheet->getStyle("A14")->applyFromArray([
					"font" => [
						"bold" => true,
						"size" => 12,
						"color" => ["rgb" => "34495E"],
					],
				]);

				// Add border for data section
				$sheet->getStyle("A3:B5")->applyFromArray([
					"borders" => [
						"allBorders" => [
							"borderStyle" => Border::BORDER_THIN,
							"color" => ["rgb" => "CCCCCC"],
						],
					],
					"fill" => [
						"fillType" => Fill::FILL_SOLID,
						"color" => ["rgb" => "F8F9F9"],
					],
				]);

				// Set row heights
				$sheet->getRowDimension(1)->setRowHeight(40);
				$sheet->getRowDimension(3)->setRowHeight(25);
				$sheet->getRowDimension(4)->setRowHeight(25);
				$sheet->getRowDimension(5)->setRowHeight(25);

				// Add footer
				$lastRow = $sheet->getHighestRow();
				$sheet->mergeCells("A{$lastRow}:F{$lastRow}");
				$sheet->setCellValue(
					"A{$lastRow}",
					"© " . date("Y") . " Sistem Keuangan - Confidential"
				);
				$sheet->getStyle("A{$lastRow}")->applyFromArray([
					"font" => [
						"italic" => true,
						"size" => 9,
						"color" => ["rgb" => "7F8C8D"],
					],
					"alignment" => [
						"horizontal" => Alignment::HORIZONTAL_CENTER,
					],
				]);
			},
		];
	}
}
