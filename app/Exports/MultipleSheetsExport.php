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
		return [
			new SummarySheet($this->reportData),
			new TrendSheet($this->reportData),
			new CategorySheet($this->reportData),
			new BudgetSheet($this->reportData),
			new AccountSheet($this->reportData),
		];
	}
}
