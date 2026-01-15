<?php

namespace Modules\Wallet\Exports;

use Modules\Wallet\Models\Transaction;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithTitle;

class TransactionsExport implements
	FromCollection,
	WithHeadings,
	WithMapping,
	ShouldAutoSize,
	WithStyles,
	WithTitle
{
	// Constructor to accept filters
	public function __construct(protected Collection $data)
	{
	}

	// Defines the data collection
	public function collection()
	{
		return $this->data;
	}

	// Maps and formats each data row
	public function map(array $transaction): array
	{
		return array_values($transaction);
	}

	// Defines the column headings
	public function headings(): array
	{
		return array_keys($this->data->first());
	}

	// Applies basic styling to the sheet
	public function styles(Worksheet $sheet)
	{
		return [
			// Make the header row bold
			1 => ["font" => ["bold" => true]],
		];
	}

	// Sets the sheet title
	public function title(): string
	{
		return "Transactions";
	}
}
