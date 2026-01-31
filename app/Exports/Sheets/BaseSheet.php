<?php
namespace Modules\Wallet\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class BaseSheet implements FromArray, WithTitle
{
	public function __construct(protected array $reportData)
	{
	}

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
