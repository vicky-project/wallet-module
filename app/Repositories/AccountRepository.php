<?php

namespace Modules\Wallet\Repositories;

use App\Models\User;
use Modules\Wallet\Helpers\Helper;
use Modules\Wallet\Models\Account;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Wallet\Enums\AccountType;

class AccountRepository extends BaseRepository
{
	public function __construct(Account $model)
	{
		parent::__construct($model);
	}

	/**
	 * Get user accounts with optional filters
	 */
	public function getUserAccounts(User $user, array $filters = []): Collection
	{
		$cachekey = Helper::generateCacheKey(
			"user_accounts",
			array_merge(["user_id" => $user->id], $filters)
		);

		return Cache::remember(
			$cachekey,
			config("wallet.cache_ttl"),
			function () use ($user, $filters) {
				$query = $this->model->where("user_id", $user->id);

				if (isset($filters["type"])) {
					$query->where("type", $filters["type"]);
				}

				if (isset($filters["is_active"])) {
					$query->where("is_active", (bool) $filters["is_active"]);
				}

				if (isset($filters["search"])) {
					$search = $filters["search"];
					$query->where(function ($q) use ($search) {
						$q->where("name", "like", "%{$search}%")
							->orWhere("account_number", "like", "%{$search}%")
							->orWhere("bank_name", "like", "%{$search}%");
					});
				}

				return $query
					->orderBy("is_default", "desc")
					->orderBy("name")
					->get();
			}
		);
	}

	/**
	 * Get user accounts paginated
	 */
	public function getUserAccountsPaginated(
		User $user,
		array $filters = [],
		int $perPage = 15
	): LengthAwarePaginator {
		$query = $this->model->where("user_id", $user->id);

		if (isset($filters["type"])) {
			$query->where("type", $filters["type"]);
		}

		if (isset($filters["is_active"])) {
			$query->where("is_active", (bool) $filters["is_active"]);
		}

		if (isset($filters["search"])) {
			$search = $filters["search"];
			$query->where(function ($q) use ($search) {
				$q->where("name", "like", "%{$search}%")
					->orWhere("account_number", "like", "%{$search}%")
					->orWhere("bank_name", "like", "%{$search}%");
			});
		}

		return $query
			->orderBy("is_default", "desc")
			->orderBy("name")
			->paginate($perPage);
	}

	/**
	 * Get default account for user
	 */
	public function getDefaultAccount(User $user): ?Account
	{
		return $this->model
			->where("user_id", $user->id)
			->default()
			->first();
	}

	/**
	 * Set account as default
	 */
	public function setDefaultAccount(Account $account): bool
	{
		DB::transaction(function () use ($account) {
			// Remove default from other accounts
			$this->model
				->where("user_id", $account->user_id)
				->default()
				->update(["is_default" => false]);

			// Set this account as default
			$account->is_default = true;
			$account->save();
		});

		return true;
	}

	/**
	 * Update account balance (atomic operation)
	 */
	public function updateBalance(
		int $accountId,
		int $amount,
		bool $increment = true
	): bool {
		$account = $this->findOrFail($accountId);

		if ($increment) {
			$account->balance = $account->balance->plus($amount);
		} else {
			$account->balance = $account->balance->minus($amount);
		}

		return $account->save();
	}

	/**
	 * Transfer balance between accounts (atomic operation)
	 */
	public function transferBalance(
		int $fromAccountId,
		int $toAccountId,
		int $amount
	): bool {
		return DB::transaction(function () use (
			$fromAccountId,
			$toAccountId,
			$amount
		) {
			$fromAccount = $this->findOrFail($fromAccountId);
			$toAccount = $this->findOrFail($toAccountId);

			// Check if from account has sufficient balance
			if ($fromAccount->balance->getAmount()->toInt() < $amount) {
				throw new \Exception("Insufficient balance in source account");
			}

			// Update balances
			$fromAccount->balance = $fromAccount->balance->minus($amount);
			$fromAccount->save();

			$toAccount->balance = $toAccount->balance->plus($amount);
			$toAccount->save();

			return true;
		});
	}

	/**
	 * Get account summary for user
	 */
	public function getAccountSummary(User $user): array
	{
		$accounts = $this->getUserAccounts($user, ["is_active" => true]);

		$totalBalance = 0;
		$assetBalance = 0;
		$liabilityBalance = 0;
		$accountCount = $accounts->count();

		foreach ($accounts as $account) {
			dd($account->balance->getAmount()->toInt());
			$balance = $account->balance->getAmount()->toInt();

			if ($account->isLiability()) {
				$liabilityBalance += $balance;
			} else {
				$assetBalance += $balance;
			}

			$totalBalance += $balance;
		}

		$netWorth = $assetBalance - abs($liabilityBalance);

		return [
			"total_accounts" => $accountCount,
			"total_balance" => $totalBalance,
			"asset_balance" => $assetBalance,
			"liability_balance" => $liabilityBalance,
			"net_worth" => $netWorth,
			"formatted_total_balance" =>
				"Rp " . number_format($totalBalance, 0, ",", "."),
			"formatted_net_worth" => "Rp " . number_format($netWorth, 0, ",", "."),
		];
	}

	/**
	 * Get account type distribution
	 */
	public function getAccountTypeDistribution(User $user): Collection
	{
		return $this->model
			->forUser($user->id)
			->active()
			->select(
				"type",
				"currency",
				DB::raw("COUNT(*) as count"),
				DB::raw("SUM(balance) as total_balance")
			)
			->groupBy(["type", "currency"])
			->get();
	}

	/**
	 * Bulk update account balances
	 */
	public function bulkUpdateBalances(array $updates): bool
	{
		return DB::transaction(function () use ($updates) {
			foreach ($updates as $update) {
				$account = $this->findOrFail($update["account_id"]);
				$account->balance = $update["balance"];
				$account->save();
			}
			return true;
		});
	}

	/**
	 * Check if account name is unique for user
	 */
	public function isNameUniqueForUser(
		User $user,
		string $name,
		int $excludeId = null
	): bool {
		$query = $this->model->where("user_id", $user->id)->where("name", $name);

		if ($excludeId) {
			$query->where("id", "!=", $excludeId);
		}

		return !$query->exists();
	}

	/**
	 * Get accounts for dropdown/select
	 */
	public function getForDropdown(User $user): array
	{
		return $this->getUserAccounts($user)
			->mapWithKeys(function ($account) {
				return [
					$account->id => "{$account->name} ({$account->type->label()}) - {$account->formatted_balance}",
				];
			})
			->toArray();
	}

	/**
	 * Get popular accounts (most used)
	 */
	public function getPopularAccounts(User $user, int $limit = 3): Collection
	{
		// For now, return accounts with highest balance
		// Later, we can implement based on transaction count
		return $this->model
			->where("user_id", $user->id)
			->where("is_active", true)
			->orderByRaw("CAST(balance as SIGNED) DESC")
			->limit($limit)
			->get();
	}

	/**
	 * Get accounts with balance changes
	 */
	public function getAccountsWithBalanceChange(
		User $user,
		int $month,
		int $year
	): Collection {
		return $this->model
			->where("user_id", $user->id)
			->where("is_active", true)
			->get()
			->map(function ($account) use ($month, $year) {
				$previousMonth = $month - 1;
				$previousYear = $year;

				if ($previousMonth === 0) {
					$previousMonth = 12;
					$previousYear = $year - 1;
				}

				$currentBalance = $account->balance->getAmount()->toInt();
				$previousIncome = $account->getIncomeForPeriod(
					$previousMonth,
					$previousYear
				);
				$previousExpense = $account->getExpenseForPeriod(
					$previousMonth,
					$previousYear
				);

				$account->balance_change =
					$currentBalance - ($previousIncome - $previousExpense);
				$account->balance_change_percentage =
					$previousIncome > 0
						? round(($account->balance_change / $previousIncome) * 100, 2)
						: 0;

				return $account;
			});
	}

	/**
	 * Get accounts requiring attention (low balance)
	 */
	public function getAccountsRequiringAttention(
		User $user,
		int $threshold = 100000
	): Collection {
		return $this->model
			->where("user_id", $user->id)
			->where("is_active", true)
			->get()
			->filter(function ($account) use ($threshold) {
				return $account->balance->getAmount()->toInt() < $threshold;
			});
	}
}
