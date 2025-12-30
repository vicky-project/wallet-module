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

	public static function getColorCategory(CategoryType|string $category)
	{
		return match ($category) {
			CategoryType::INCOME, CategoryType::INCOME->value => "text-success",
			CategoryType::EXPENSE, CategoryType::EXPENSE->value => "text-danger",
			CategoryType::TRANSFER, CategoryType::TRANSFER->value => "text-info",
		};
	}
}
