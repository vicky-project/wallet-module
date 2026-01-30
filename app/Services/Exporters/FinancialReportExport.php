<?php

namespace Modules\Wallet\Services\Exporters;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeExport;
use Maatwebsite\Excel\Events\BeforeWriting;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class FinancialReportExport implements WithMultipleSheets, WithEvents
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

		// Cover Page
		$sheets[] = new CoverSheet($this->reportData);

		// Executive Summary
		$sheets[] = new SummarySheet($this->reportData);

		// Detailed Sheets
		$sheets[] = new TrendSheet($this->reportData);
		$sheets[] = new CategorySheet($this->reportData);
		$sheets[] = new BudgetSheet($this->reportData);
		$sheets[] = new AccountSheet($this->reportData);

		// Transaction Activity
		$sheets[] = new TransactionSheet($this->reportData);

		return $sheets;
	}

	public function registerEvents(): array
	{
		return [
			BeforeExport::class => function (BeforeExport $event) {
				$event->writer->getProperties()->setTitle("Laporan Keuangan");
				$event->writer->getProperties()->setSubject("Financial Report");
				$event->writer->getProperties()->setCreator("Sistem Keuangan");
				$event->writer->getProperties()->setCompany("Financial App");
				$event->writer
					->getProperties()
					->setDescription(
						"Laporan keuangan lengkap dengan analisis dan chart"
					);
				$event->writer
					->getProperties()
					->setKeywords("keuangan, laporan, excel, pdf");
				$event->writer->getProperties()->setCategory("Financial Report");
			},
			BeforeWriting::class => function (BeforeWriting $event) {
				// Set default style for all sheets
				$event->writer
					->getDefaultStyle()
					->getFont()
					->setName("Arial")
					->setSize(10);
			},
		];
	}
}
