<?php

namespace Modules\Wallet\Traits;

use Brick\Money\Money;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasMoney
{
	/**
	 * Convert integer amount to Money object
	 */
	protected function money(string $field): Attribute
	{
		return Attribute::make(
			get: fn($value) => $value ? Money::ofMinor($value, "IDR") : null,
			set: fn($value) => $this->convertToMinorAmount($value)
		);
	}

	/**
	 * Convert various input types to minor amount (integer)
	 */
	protected function convertToMinorAmount($value): ?int
	{
		if (is_null($value)) {
			return null;
		}

		if ($value instanceof Money) {
			return $value->getMinorAmount()->toInt();
		}

		if (is_numeric($value)) {
			return Money::of($value, "IDR")
				->getMinorAmount()
				->toInt();
		}

		if (is_string($value)) {
			// Remove currency symbols, thousand separators, and convert to float
			$cleanValue = preg_replace("/[^0-9\.\-]/", "", $value);
			return Money::of($cleanValue, "IDR")
				->getMinorAmount()
				->toInt();
		}

		throw new \InvalidArgumentException("Invalid money value");
	}

	/**
	 * Format Money for display
	 */
	public function formatMoney(Money $money): string
	{
		return $money->formatTo("id_ID");
	}

	/**
	 * Get formatted amount for a field
	 */
	public function getFormattedAmount(string $field): string
	{
		$amount = $this->{$field};
		if (!$amount instanceof Money) {
			$amount = Money::ofMinor($amount, "IDR");
		}
		return $this->formatMoney($amount);
	}
}
