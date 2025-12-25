<?php

namespace Modules\Wallet\Casts;

use Brick\Money\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Brick\Math\RoundingMode;
use InvalidArgumentException;

class MoneyCast implements CastsAttributes
{
	/**
	 * Transform the stored integer and currency into a Money object.
	 */
	public function get(
		Model $model,
		string $key,
		mixed $value,
		array $attributes
	): mixed {
		// Determine the currency. This example checks for a `currency` attribute on the same model.
		// You might need to adjust the logic based on your table structure.
		$currency =
			$attributes["currency"] ??
			($model->currency ?? config("finance.default_currency", "USD"));

		// Create a Money instance from the minor unit (cents) stored in the database.
		return Money::ofMinor($value, $currency, null, RoundingMode::DOWN);
	}

	/**
	 * Transform the input value (Money object, string, or float) into a storable integer.
	 */
	public function set(
		Model $model,
		string $key,
		mixed $value,
		array $attributes
	): mixed {
		if ($value instanceof Money) {
			// If it's already a Money object, get its amount in minor units.
			return $value->getMinorAmount()->toInt();
		}

		// If it's a numeric string or float, create a Money object first.
		// The currency is determined from existing attributes or a default.
		$currency =
			$attributes["currency"] ??
			($model->currency ?? config("finance.default_currency", "USD"));

		try {
			// Money::of() handles string input like '19.99' correctly.
			$money = Money::of($value, $currency, null, RoundingMode::DOWN);
			return $money->getMinorAmount()->toInt();
		} catch (\Exception $e) {
			throw new InvalidArgumentException(
				"Invalid money value provided for {$key}: {$value}"
			);
		}
	}
}
