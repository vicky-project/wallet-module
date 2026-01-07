<?php

namespace Modules\Wallet\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Brick\Money\Money;
use Brick\Math\RoundingMode;

abstract class BaseRepository
{
	protected Model $model;

	public function __construct(Model $model)
	{
		$this->model = $model;
	}

	/**
	 * Convert value to Money object
	 */
	protected function toMoney($amount, string $currency = "IDR"): Money
	{
		if ($amount instanceof Money) {
			return $amount;
		}

		if (is_numeric($amount) || is_int($amount)) {
			return Money::of($amount, $currency);
		}

		if (is_null($amount)) {
			return Money::zero($amount);
		}

		throw new \InvalidArgumentException("Invalid amount format");
	}

	/**
	 * Convert Money to database format (integer for subunits)
	 */
	protected function toDatabaseAmount(Money $money): int
	{
		return $money->getAmount()->toInt();
	}

	/**
	 * Convert database amount to Money
	 */
	protected function fromDatabaseAmount(
		int $amount,
		?string $currency = null,
		bool $isInt = false
	): Money {
		$currency =
			$currency ??
			($money->getCurrency()->getCurrencyCode() ??
				config("wallet.default_currency", "USD"));

		return $isInt
			? Money::of($amount, $currency)
			: Money::ofMinor($amount, $currency);
	}

	/**
	 * Format Money for display
	 */
	protected function formatMoney(Money $money): string
	{
		return $money->formatTo("id_ID");
	}

	/**
	 * Create new record
	 */
	public function create(array $data): Model
	{
		return $this->model->create($data);
	}

	/**
	 * Update record
	 */
	public function update(int $id, array $data): bool
	{
		$model = $this->model->findOrFail($id);
		return $model->update($data);
	}

	/**
	 * Delete record
	 */
	public function delete(int $id): bool
	{
		return $this->model->destroy($id);
	}

	/**
	 * Find by ID
	 */
	public function find(int $id): ?Model
	{
		return $this->model->find($id);
	}

	/**
	 * Get all records
	 */
	public function all(): Collection
	{
		return $this->model->all();
	}

	/**
	 * Get paginated records
	 */
	public function paginate(int $perPage = 15)
	{
		return $this->model->paginate($perPage);
	}

	public function getModel()
	{
		return $this->model;
	}
}
