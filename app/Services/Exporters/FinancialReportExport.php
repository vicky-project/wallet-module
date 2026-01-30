<?php

namespace Modules\Wallet\Services\Exporters;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Exportable;

class FinancialReportExport implements WithMultipleSheets
{
	use Exportable;

	protected $reportData;

	public function __construct(array $reportData)
	{
		$this->reportData = $reportData;
	}

	public function sheets(): array
	{
		$sheets = [];

		// Sheet 1: Summary
		$sheets[] = new SummarySheet($this->reportData);

		// Sheet 2: Trend Analysis
		$sheets[] = new TrendSheet($this->reportData);

		// Sheet 3: Category Analysis
		// $sheets[] = new CategorySheet($this->reportData);

		// Sheet 4: Budget Analysis
		// $sheets[] = new BudgetSheet($this->reportData);

		// Sheet 5: Account Analysis
		// $sheets[] = new AccountSheet($this->reportData);

		return $sheets;
	}
}
