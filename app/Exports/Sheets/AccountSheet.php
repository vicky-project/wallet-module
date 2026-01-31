<?php

namespace Modules\Wallet\Exports\Sheets;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AccountSheet implements WithHeadings, WithStyles
{
	public function __construct(array $reportData)
	{
		parent::_construct($reportData);
	}

	public function array(): array
	{
		$accountData = $this->reportData["report_data"]["account_analysis"] ?? [
			"labels" => [],
			"datasets" => [["data" => []]],
		];

		$labels = $accountData["labels"] ?? [];
		$balances = $accountData["datasets"][0]["data"] ?? [];
		$totalBalance = array_sum($balances);

		$data = [
			["DISTRIBUSI SALDO AKUN"],
			[""],
			["Akun", "Saldo", "Persentase", "Tipe"],
		];

		foreach ($labels as $index => $label) {
			$balance = $balances[$index] ?? 0;
			$percentage = $totalBalance > 0 ? ($balance / $totalBalance) * 100 : 0;

			$data[] = [
				$label,
				$this->formatCurrency($balance),
				number_format($percentage, 2) . "%",
				$this->getAccountType($label),
			];
		}

		if (count($labels) > 0) {
			$data[] = ["TOTAL", $this->formatCurrency($totalBalance), "100%", ""];
		} else {
			$data[] = ["Tidak ada data akun"];
		}

		return $data;
	}

	public function headings(): array
	{
		return [];
	}

	public function title(): string
	{
		return "Akun";
	}

	public function styles(Worksheet $sheet)
	{
		// Set column widths
		$sheet->getColumnDimension("A")->setWidth(25);
		$sheet->getColumnDimension("B")->setWidth(20);
		$sheet->getColumnDimension("C")->setWidth(15);
		$sheet->getColumnDimension("D")->setWidth(15);

		// Style judul
		$sheet
			->getStyle("A1")
			->getFont()
			->setBold(true)
			->setSize(14);
		$sheet->mergeCells("A1:D1");

		// Style header tabel
		$sheet
			->getStyle("A3:D3")
			->getFont()
			->setBold(true);

		// Format angka untuk kolom B
		$lastRow = count($this->array());
		for ($row = 4; $row <= $lastRow; $row++) {
			$sheet
				->getStyle("B{$row}")
				->getNumberFormat()
				->setFormatCode("#,##0");
		}

		// Border
		$sheet
			->getStyle("A3:D" . $lastRow)
			->getBorders()
			->getAllBorders()
			->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

		// Alignment
		$sheet
			->getStyle("A3:D" . $lastRow)
			->getAlignment()
			->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
		$sheet
			->getStyle("B4:B" . $lastRow)
			->getAlignment()
			->setHorizontal(
				\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT
			);
		$sheet
			->getStyle("C4:C" . $lastRow)
			->getAlignment()
			->setHorizontal(
				\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
			);
	}

	private function getAccountType($label)
	{
		$labelLower = strtolower($label);

		if (
			strpos($labelLower, "tabung") !== false ||
			strpos($labelLower, "deposit") !== false
		) {
			return "Tabungan";
		}

		if (
			strpos($labelLower, "tunai") !== false ||
			strpos($labelLower, "cash") !== false
		) {
			return "Tunai";
		}

		if (
			strpos($labelLower, "kartu") !== false ||
			strpos($labelLower, "credit") !== false
		) {
			return "Kartu";
		}

		if (strpos($labelLower, "invest") !== false) {
			return "Investasi";
		}

		return "Lainnya";
	}
}
