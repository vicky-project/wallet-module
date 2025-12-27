<?php
namespace Modules\Wallet\Helpers;

use Modules\Wallet\Enums\CategoryType;

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

	public static function getColorCategory(CategoryType $category)
	{
		return match ($category) {
			CategoryType::INCOME => "text-success",
			CategoryType::EXPENSE => "text-danger",
			CategoryType::TRANSFER => "text-info",
		};
	}
}
