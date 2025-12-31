<?php

namespace Modules\Wallet\Repositories;

use App\Models\User;
use Brick\Money\Money;
use Carbon\Carbon;
use Modules\Wallet\Models\SavingGoal;

class SavingGoalRepository extends BaseRepository
{
	public function __construct(SavingGoal $model)
	{
		parent::__construct($model);
	}

	/**
	 * Create saving goal with Money amounts
	 */
	public function createSavingGoal(array $data, User $user): SavingGoal
	{
		$data["user_id"] = $user->id;

		// Convert amounts to Money
		$data["target_amount"] = $this->toDatabaseAmount(
			$this->toMoney($data["target_amount"])
		);

		if (isset($data["current_amount"])) {
			$data["current_amount"] = $this->toDatabaseAmount(
				$this->toMoney($data["current_amount"])
			);
		} else {
			$data["current_amount"] = 0;
		}

		return $this->create($data);
	}

	/**
	 * Update saving goal
	 */
	public function updateSavingGoal(int $id, array $data): SavingGoal
	{
		if (isset($data["target_amount"])) {
			$data["target_amount"] = $this->toDatabaseAmount(
				$this->toMoney($data["target_amount"])
			);
		}

		if (isset($data["current_amount"])) {
			$data["current_amount"] = $this->toDatabaseAmount(
				$this->toMoney($data["current_amount"])
			);
		}

		$this->update($id, $data);
		return $this->find($id);
	}

	/**
	 * Add amount to saving goal
	 */
	public function addAmount(int $id, $amount): SavingGoal
	{
		$goal = $this->find($id);

		$currentAmount = $this->fromDatabaseAmount($goal->current_amount);
		$addAmount = $this->toMoney($amount);

		$newAmount = $currentAmount->plus($addAmount);

		$goal->current_amount = $this->toDatabaseAmount($newAmount);

		// Check if goal is completed
		$targetAmount = $this->fromDatabaseAmount($goal->target_amount);
		if ($newAmount->isGreaterThanOrEqualTo($targetAmount)) {
			$goal->is_completed = true;
			$goal->completed_at = Carbon::now();
			$goal->current_amount = $goal->target_amount; // Cap at target
		}

		$goal->save();

		return $goal;
	}

	/**
	 * Get active saving goals
	 */
	public function getActiveGoals(User $user): Collection
	{
		return $this->model
			->where("user_id", $user->id)
			->where("is_completed", false)
			->orderBy("priority", "desc")
			->orderBy("target_date", "asc")
			->get()
			->map(function ($goal) {
				$goal->progress_percentage = $this->calculateProgress($goal);
				$goal->formatted_target_amount = $this->formatMoney(
					$this->fromDatabaseAmount($goal->target_amount)
				);
				$goal->formatted_current_amount = $this->formatMoney(
					$this->fromDatabaseAmount($goal->current_amount)
				);
				$goal->remaining_amount = $this->formatMoney(
					$this->fromDatabaseAmount($goal->target_amount)->minus(
						$this->fromDatabaseAmount($goal->current_amount)
					)
				);
				return $goal;
			});
	}

	/**
	 * Calculate progress percentage
	 */
	private function calculateProgress(SavingGoal $goal): float
	{
		$target = $this->fromDatabaseAmount($goal->target_amount);
		$current = $this->fromDatabaseAmount($goal->current_amount);

		if ($target->isZero()) {
			return 0;
		}

		$percentage = $current
			->getAmount()
			->dividedBy(
				$target->getAmount(),
				4 // 4 decimal places for percentage
			)
			->multipliedBy(100);

		return min(100, $percentage->toFloat());
	}
}
