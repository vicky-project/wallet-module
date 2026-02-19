<?php

namespace Modules\Wallet\Repositories;

use Illuminate\Support\Collection;
use Brick\Money\Money;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

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
	public function toMoney(
		$amount,
		string $currency = "IDR",
		bool $isInteger = true
	): Money {
		if ($amount instanceof Money) {
			return $amount;
		}

		if (is_numeric($amount) || is_int($amount)) {
			return $isInteger
				? Money::of($amount, $currency)
				: Money::ofMinor($amount, $currency);
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
		$currency = $currency ?? config("wallet.default_currency", "USD");

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
		$model = $this->findOrFail($id);
		return $model->update($data);
	}

	/**
	 * Delete record
	 */
	public function delete(int $id): bool
	{
		$model = $this->findOrFail($id);
		return $model->delete();
	}

	/**
	 * Restore soft delete record
	 */
	public function restore(int $id): bool
	{
		return $this->model
			->withTrashed()
			->where("id", $id)
			->restore();
	}

	/**
	 * Force delete record
	 */
	public function forceDelete(int $id): bool
	{
		$model = $this->model->withTrashed()->findOrFail($id);
		return $model->forceDelete();
	}

	/**
	 * Find by ID
	 */
	public function find(int $id, array $with = []): ?Model
	{
		return $this->model->with($with)->find($id);
	}

	/**
	 * Find by ID
	 */
	public function findOrFail(int $id, array $with = []): Model
	{
		return $this->model->with($with)->findOrFail($id);
	}

	/**
	 * Get all records
	 */
	public function all(array $columns = ["*"]): Collection
	{
		return $this->model->all($columns);
	}

	/**
	 * Get paginated records
	 */
	public function paginate(
		int $perPage = 15,
		array $columns = ["*"]
	): LengthAwarePaginator {
		return $this->model->paginate($perPage, $columns);
	}

	public function getModel(): Model
	{
		return $this->model;
	}

	public function query()
	{
		return $this->model->newQuery();
	}

	/**
	 * Update or create
	 */
	public function updateOrCreate(array $attributes, array $values = []): Model
	{
		return $this->model->updateOrCreate($attributes, $values);
	}

	/**
	 * First or create
	 */
	public function firstOrCreate(array $attributes, array $values = []): Model
	{
		return $this->model->firstOrCreate($attributes, $values);
	}
}
