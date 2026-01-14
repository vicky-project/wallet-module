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
		$currency =
			$attributes["currency"] ??
			($model->currency ?? config("wallet.default_currency", "USD"));

		if (is_null($value)) {
			$value = Money::zero($currency);
		}

		if (!is_numeric($value)) {
			$value = Money::zero($currency);
		}

		// Create a Money instance from the minor unit (cents) stored in the database.
		return Money::ofMinor($value, $currency);
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
		if ($value === null || $value === "" || $value === 0) {
			return 0;
		}

		if ($value instanceof Money) {
			// If it's already a Money object, get its amount.
			return $value->getMinorAmount()->toInt();
		}

		// If it's a numeric string or float, create a Money object first.
		// The currency is determined from existing attributes or a default.
		$currency =
			$attributes["currency"] ??
			($model->currency ?? config("wallet.default_currency", "USD"));

		try {
			if (is_string($value)) {
				$value = trim($value);
				if (empty($value)) {
					return 0;
				}

				$value = preg_replace("/[^0-9.,-]/", "", $value);
				$value = str_replace(",", ".", $value);
			}

			if (!is_numeric($value)) {
				throw new InvalidArgumentException(
					"Invalid money value provided for {$key}:{$value}"
				);
			}

			// Money::of() handles string input like '19.99' correctly.
			$money = Money::of($value, $currency);
			return $money->getMinorAmount()->toInt();
		} catch (\Exception $e) {
			throw new InvalidArgumentException(
				"Invalid money value provided for {$key}: {$value}"
			);
		}
	}
}
