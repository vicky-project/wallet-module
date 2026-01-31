<?php
namespace Modules\Wallet\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

abstract class BaseSheet implements FromArray, WithTitle
{
	protected array $reportData;

	public function __construct(array $reportData)
	{
		$this->reportData = $reportData;
	}

	abstract public function array(): array;

	protected function formatCurrency($value)
	{
		if (!is_numeric($value)) {
			return 0;
		}

		$currency = isset($this->reportData["currency"])
			? $this->reportData["currency"]
			: null;

		return money($value, $currency);
	}
}
