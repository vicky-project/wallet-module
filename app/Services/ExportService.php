<?php

namespace Modules\Wallet\Services;

use Maatwebsite\Excel\Facades\Excel;
use Modules\Wallet\Exports\CategorySheet;
use Modules\Wallet\Exports\MultipleSheetsExport;
use Modules\Wallet\Services\Exporters\BudgetSheet;
use Modules\Wallet\Services\Exporters\AccountSheet;
use Modules\Wallet\Services\Exporters\TrendSheet;
use Modules\Wallet\Services\Exporters\SummarySheet;

class ExportService
{
	public function exportExcel(array $reportData)
	{
		// Create temporary file
		$tempFile = tempnam(sys_get_temp_dir(), "report_") . ".xlsx";

		// Create Excel with multiple sheets
		Excel::store(new MultipleSheetsExport($reportData), $tempFile);

		// Read and return content
		$content = file_get_contents($tempFile);
		unlink($tempFile);

		return $content;
	}
}
