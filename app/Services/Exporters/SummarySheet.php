<?php

namespace Modules\Wallet\Services\Exporters;

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
			["", ""],
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
		return ["Item", "Nilai"];
	}

	public function title(): string
	{
		return "Ringkasan";
	}

	public function styles(Worksheet $sheet)
	{
		return [
			1 => ["font" => ["bold" => true, "size" => 12]],
			"A1:B1" => [
				"fill" => [
					"fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
					"color" => ["rgb" => "E8F5E9"],
				],
			],
		];
	}

	private function formatCurrency($value)
	{
		if (is_string($value) && strpos($value, "Rp") !== false) {
			return $value;
		}

		$amount = is_numeric($value) ? $value / 100 : 0;
		return "Rp " . number_format($amount, 0, ",", ".");
	}
}
