<?php

namespace Modules\Wallet\Repositories;

use App\Models\User;
use Brick\Money\Money;
use Brick\Math\RoundingMode;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Wallet\Models\Account;
use Modules\Wallet\Enums\AccountType;

class AccountRepository extends BaseRepository
{
	public function __construct(Account $model)
	{
		parent::__construct($model);
	}

	public function accounts(User $user)
	{
		return $this->model
			->where("user_id", $user->id)
			->orderBy("is_default", "desc")
			->orderBy("type")
			->orderBy("name")
			->get();
	}

	public function getAccountsMapping(Collection $accountsCollection): Collection
	{
		return $accountsCollection->map(function ($account) {
			$initial = $this->fromDatabaseAmount(
				$account->initial_balance->getAmount()->toInt()
			);
			$current = $this->fromDatabaseAmount(
				$account->current_balance->getAmount()->toInt()
			);
			$change = $current->minus($initial);

			$account->balance_change_amount = $change;
			$account->balance_change_formatted = $this->formatMoney($change);
			$account->is_positif_change = !$change->isNegative();

			return $account;
		});
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
			$this->toMoney(
				$data["current_balance"] ?? ($data["initial_balance"] ?? 0)
			)
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
	 * Get total balance across all accounts
	 */
	public function getTotalBalance(User $user): Money
	{
		$total = $this->model->where("user_id", $user->id)->sum("current_balance");

		return $this->fromDatabaseAmount($total);
	}

	/**
	 * Get account statistics for dashboard
	 */
	public function getAccountStats(
		Collection $accountsCollection,
		User $user
	): array {
		$totalBalance = $this->getTotalBalance($user);

		// Count accounts by type
		foreach (AccountType::cases() as $type) {
			$typeCounts[$type->value] = $accountsCollection
				->where("type.value", $type->value)
				->count();
		}

		// Sum balances by type
		$typeBalances = [];
		foreach ($accountsCollection as $account) {
			$type = $account->type->value;
			if (!isset($typeBalances[$type])) {
				$typeBalances[$type] = Money::of(
					0,
					$account->currency ?? config("wallet.default_currency", "USD"),
					null,
					RoundingMode::DOWN
				);
			}
			$typeBalances[$type] = $typeBalances[$type]->plus(
				$this->fromDatabaseAmount(
					$account->current_balance->getAmount()->toInt()
				)
			);
		}

		// Format type balances
		$formattedTypeBalances = [];
		foreach ($typeBalances as $type => $balance) {
			$formattedTypeBalances[$type] = $this->formatMoney($balance);
		}

		return [
			"total_accounts" => $accountsCollection->count(),
			"total_balance" => $this->formatMoney($totalBalance),
			"total_balance_raw" => $totalBalance,
			"default_account" => $accountsCollection
				->where("is_default", true)
				->first(),
			"accounts_by_type" => $typeCounts,
			"balances_by_type" => $formattedTypeBalances,
			"recent_accounts" => $accountsCollection->take(5),
		];
	}

	/**
	 * Get recent transactions for account
	 */
	public function getRecentTransactions(
		Account $account,
		int $limit = 10
	): Collection {
		if ($account->isEmpty()) {
			return collect();
		}

		return $account
			->transactions()
			->with("category")
			->latest()
			->limit($limit)
			->get()
			->map(function ($transaction) {
				return [
					"id" => $transaction->id,
					"date" => $transaction->transaction_date,
					"description" => $transaction->description,
					"amount" => $this->formatMoney(
						$this->fromDatabaseAmount($transaction->amount)
					),
					"amount_raw" => $transaction->amount,
					"type" => $transaction->type,
					"category" => $transaction->category->name ?? "Tanpa Kategori",
					"category_icon" => $transaction->category->icon ?? "bi-tag",
				];
			});
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
	public function getBalanceHistory(Account $account, int $days = 30): array
	{
		try {
			if ($account->isEmpty()) {
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
				$balance = $this->fromDatabaseAmount(
					$account->current_balance->getAmount()->toInt()
				)->plus(
					Money::of(
						rand(-50000, 50000),
						$account->currency ?? config("wallet.default_currency", "USD"),
						null,
						RoundingMode::DOWN
					),
					RoundingMode::DOWN
				);

				$balances->push($balance);
			}

			return [
				"dates" => $dates,
				"balances" => $balances->map(function ($balance) {
					return $this->formatMoney(
						$this->fromDatabaseAmount(
							$balance->getAmount()->toInt(),
							$account->currency ?? config("wallet.default_currency", "USD")
						)
					);
				}),
			];
		} catch (\Exception $e) {
			throw $e;
		}
	}

	/**
	 * Get primary account (account with highest balance)
	 */
	public function getPrimaryAccount(User $user): ?Account
	{
		return $this->model
			->where("user_id", $user->id)
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

		$currentBalance = $this->fromDatabaseAmount(
			$account->current_balance->getAmount()->toInt(),
			$account->currency ?? config("wallet.default_currency", "USD")
		);

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
		$currentBalance = $this->fromDatabaseAmount(
			$account->current_balance->getAmount()->toInt()
		);

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
					$this->fromDatabaseAmount(
						$account->current_balance->getAmount()->toInt()
					)
				);
				return [
					$account->id => "{$account->name} ({$balance})",
				];
			})
			->toArray();
	}
}
