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
		$income = CategoryType::INCOME;
		$expense = CategoryType::EXPENSE;
		$transfer = CategoryType::TRANSFER;

		return match ($category) {
			CategoryType::INCOME, $income => "text-success",
			CategoryType::EXPENSE, $expense => "text-danger",
			CategoryType::TRANSFER, $transfer => "text-info",
		};
	}
}
