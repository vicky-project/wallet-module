<?php
namespace Modules\Wallet\Helpers;

class Helper
{
	public static function listCurrencies()
	{
		return collect(config("money.currencies"))
			->keys()
			->mapWithKeys(
				fn($currency) => [
					$currency =>
						config("money.currencies")[$currency]["name"] .
						" (" .
						config("money.currencies")[$currency]["symbol"] .
						")",
				]
			)
			->toArray();
	}
}
