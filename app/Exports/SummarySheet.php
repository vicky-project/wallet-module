<?php

namespace Modules\Wallet\Exports;

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

		$data = [
			["RINGKASAN KEUANGAN"],
			[""],
			["Item", "Nilai"],
			[
				"Total Pendapatan",
				$this->formatCurrency($summary["total_income"] ?? 0),
			],
			[
				"Total Pengeluaran",
				$this->formatCurrency($summary["total_expense"] ?? 0),
			],
			[
				"Saldo Bersih (Net Flow)",
				$this->formatCurrency($summary["net_flow"] ?? 0),
			],
			["Jumlah Transaksi Pendapatan", $summary["income_count"] ?? 0],
			["Jumlah Transaksi Pengeluaran", $summary["expense_count"] ?? 0],
			[
				"Total Transfer",
				$this->formatCurrency($summary["total_transfer"] ?? 0),
			],
			[""],
			["Periode Laporan", $this->reportData["period"] ?? ""],
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
		// Return empty karena header sudah ada di array()
		return [];
	}

	public function title(): string
	{
		return "Ringkasan";
	}

	public function styles(Worksheet $sheet)
	{
		// Set column widths
		$sheet->getColumnDimension("A")->setWidth(30);
		$sheet->getColumnDimension("B")->setWidth(25);

		// Style judul (row 1)
		$sheet
			->getStyle("A1")
			->getFont()
			->setBold(true)
			->setSize(14);
		$sheet->mergeCells("A1:B1");

		// Style header tabel (row 3)
		$sheet
			->getStyle("A3:B3")
			->getFont()
			->setBold(true);

		// Style untuk angka (format Rupiah)
		$lastRow = count($this->array());
		for ($row = 4; $row <= 9; $row++) {
			if ($row !== 7 && $row !== 8) {
				// Skip row 7 & 8 (count transaksi)
				$sheet
					->getStyle("B{$row}")
					->getNumberFormat()
					->setFormatCode("#,##0");
			}
		}

		// Border untuk data
		$sheet
			->getStyle("A3:B" . $lastRow)
			->getBorders()
			->getAllBorders()
			->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
	}

	private function formatCurrency($value)
	{
		if (!is_numeric($value)) {
			return "0";
		}
		$amount = $value / 100;
		return $amount;
	}
}
