<?php

namespace Modules\Wallet\Repositories;

use App\Models\User;
use Brick\Money\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Wallet\Models\Account;

class AccountRepository extends BaseRepository
{
	public function __construct(Account $model)
	{
		parent::__construct($model);
	}

	/**
	 * Create new account with Money amounts
	 */
	public function createAccount(array $data, User $user): Account
	{
		$data["user_id"] = $user->id;

		// Convert balances to Money
		$data["initial_balance"] = $this->toDatabaseAmount(
			$this->toMoney($data["initial_balance"] ?? 0)
		);

		$data["current_balance"] = $this->toDatabaseAmount(
			$this->toMoney($data["current_balance"] ?? $data["initial_balance"])
		);

		return $this->create($data);
	}

	/**
	 * Update account
	 */
	public function updateAccount(int $id, array $data): Account
	{
		if (isset($data["initial_balance"])) {
			$data["initial_balance"] = $this->toDatabaseAmount(
				$this->toMoney($data["initial_balance"])
			);
		}

		if (isset($data["current_balance"])) {
			$data["current_balance"] = $this->toDatabaseAmount(
				$this->toMoney($data["current_balance"])
			);
		}

		$this->update($id, $data);
		return $this->find($id);
	}

	/**
	 * Get active accounts with formatted balances
	 */
	public function getActiveAccounts(User $user): Collection
	{
		return $this->model
			->where("user_id", $user->id)
			->orderBy("type")
			->orderBy("name")
			->get()
			->map(function ($account) {
				$account->formatted_current_balance = $this->formatMoney(
					$this->fromDatabaseAmount($account->current_balance)
				);
				$account->formatted_initial_balance = $this->formatMoney(
					$this->fromDatabaseAmount($account->initial_balance)
				);

				// Calculate balance change
				$initial = $this->fromDatabaseAmount($account->initial_balance);
				$current = $this->fromDatabaseAmount($account->current_balance);
				$change = $current->minus($initial);

				$account->balance_change = $this->formatMoney($change);
				$account->balance_change_raw = $change;
				$account->is_positive_change = !$change->isNegative();

				return $account;
			});
	}

	/**
	 * Get total balance across all accounts
	 */
	public function getTotalBalance(User $user): Money
	{
		$total = $this->model->where("user_id", $user->id)->sum("current_balance");

		return $this->fromDatabaseAmount($total);
	}

	/**
	 * Get accounts by type with balance summary
	 */
	public function getByTypeWithSummary(User $user): array
	{
		$accounts = $this->getActiveAccounts($user);

		$summary = [
			"cash" => [
				"total" => Money::of(0, "IDR"),
				"count" => 0,
				"accounts" => [],
			],
			"bank" => [
				"total" => Money::of(0, "IDR"),
				"count" => 0,
				"accounts" => [],
			],
			"ewallet" => [
				"total" => Money::of(0, "IDR"),
				"count" => 0,
				"accounts" => [],
			],
			"credit_card" => [
				"total" => Money::of(0, "IDR"),
				"count" => 0,
				"accounts" => [],
			],
			"investment" => [
				"total" => Money::of(0, "IDR"),
				"count" => 0,
				"accounts" => [],
			],
		];

		foreach ($accounts as $account) {
			$type = $account->type;
			if (isset($summary[$type])) {
				$balance = $this->fromDatabaseAmount($account->current_balance);
				$summary[$type]["total"] = $summary[$type]["total"]->plus($balance);
				$summary[$type]["count"]++;
				$summary[$type]["accounts"][] = $account;
			}
		}

		// Format totals
		foreach ($summary as $type => $data) {
			$summary[$type]["formatted_total"] = $this->formatMoney($data["total"]);
		}

		return $summary;
	}

	/**
	 * Get account balance history (last 30 days)
	 */
	public function getBalanceHistory(int $accountId, int $days = 30): array
	{
		$account = $this->find($accountId);
		if (!$account) {
			return [
				"dates" => [],
				"balances" => [],
			];
		}

		$dates = collect();
		$balances = collect();

		$today = now();

		for ($i = $days - 1; $i >= 0; $i--) {
			$date = $today
				->copy()
				->subDays($i)
				->format("Y-m-d");
			$dates->push($date);

			// Simulate balance change for demo
			// In production, this would query transaction history
			$balance = $this->fromDatabaseAmount($account->current_balance)->plus(
				Money::of(rand(-50000, 50000), "IDR")
			);

			$balances->push($balance->getAmount()->toInt());
		}

		return [
			"dates" => $dates,
			"balances" => $balances->map(function ($balance) {
				return $this->formatMoney($this->fromDatabaseAmount($balance));
			}),
		];
	}

	/**
	 * Get primary account (account with highest balance)
	 */
	public function getPrimaryAccount(User $user): ?Account
	{
		return $this->model
			->where("user_id", $user->id)
			->where("is_active", true)
			->orderBy("current_balance", "desc")
			->first();
	}

	/**
	 * Update account balance
	 */
	public function updateBalance(
		int $accountId,
		Money $amount,
		string $operation = "add"
	): Account {
		$account = $this->find($accountId);

		$currentBalance = $this->fromDatabaseAmount($account->current_balance);

		if ($operation === "add") {
			$newBalance = $currentBalance->plus($amount);
		} elseif ($operation === "subtract") {
			$newBalance = $currentBalance->minus($amount);
		} else {
			throw new \InvalidArgumentException(
				'Operation must be "add" or "subtract"'
			);
		}

		$account->current_balance = $this->toDatabaseAmount($newBalance);
		$account->save();

		return $account;
	}

	/**
	 * Check if account has sufficient balance
	 */
	public function hasSufficientBalance(int $accountId, Money $amount): bool
	{
		$account = $this->find($accountId);
		$currentBalance = $this->fromDatabaseAmount($account->current_balance);

		return $currentBalance->isGreaterThanOrEqualTo($amount);
	}

	/**
	 * Get accounts for dropdown selection
	 */
	public function getForDropdown(User $user): array
	{
		return $this->model
			->where("user_id", $user->id)
			->orderBy("name")
			->get()
			->mapWithKeys(function ($account) {
				$balance = $this->formatMoney(
					$this->fromDatabaseAmount($account->current_balance)
				);
				return [
					$account->id => "{$account->name} ({$balance})",
				];
			})
			->toArray();
	}
}
