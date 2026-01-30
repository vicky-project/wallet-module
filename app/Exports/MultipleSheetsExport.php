<?php

namespace Modules\Wallet\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MultipleSheetsExport implements WithMultipleSheets
{
	protected $reportData;

	public function __construct(array $reportData)
	{
		$this->reportData = $reportData;
	}

	public function sheets(): array
	{
		$sheets = [];

		// Buat sheet hanya jika data ada
		$sheets[] = new SummarySheet($this->reportData);
		$sheets[] = new TrendSheet($this->reportData);
		$sheets[] = new CategorySheet($this->reportData);
		$sheets[] = new BudgetSheet($this->reportData);
		$sheets[] = new AccountSheet($this->reportData);

		return $sheets;
	}
}
