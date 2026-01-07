<?php

namespace Modules\Wallet\Services;

use App\Models\User;
use Modules\Wallet\Models\Account;
use Modules\Wallet\Repositories\AccountRepository;
use Modules\Wallet\Enums\AccountType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Brick\Money\Money;

class AccountService
{
	protected AccountRepository $repository;

	public function __construct(AccountRepository $repository)
	{
		$this->repository = $repository;
	}

	/**
	 * Create new account with validation
	 */
	public function createAccount(User $user, array $data): Account
	{
		// Validate account name uniqueness
		if (!$this->repository->isNameUniqueForUser($user, $data["name"])) {
			throw new \Exception("Account name already exists for this user");
		}

		// Set user_id
		$data["user_id"] = $user->id;

		// If this is the first account, set as default
		if (!$this->repository->getUserAccounts($user)->count()) {
			$data["is_default"] = true;
		}

		// If is_default is true, ensure only one default account
		if (isset($data["is_default"]) && $data["is_default"]) {
			$this->ensureSingleDefaultAccount($user);
		}

		// Create account
		return $this->repository->create($data);
	}

	/**
	 * Update existing account
	 */
	public function updateAccount(Account $account, array $data): Account
	{
		// Validate account name uniqueness (excluding current account)
		if (isset($data["name"]) && $data["name"] !== $account->name) {
			if (
				!$this->repository->isNameUniqueForUser(
					$account->user,
					$data["name"],
					$account->id
				)
			) {
				throw new \Exception("Account name already exists for this user");
			}
		}

		// If is_default is true, ensure only one default account
		if (isset($data["is_default"]) && $data["is_default"]) {
			$this->ensureSingleDefaultAccount($account->user, $account->id);
		}

		// Update account
		$this->repository->update($account->id, $data);
		return $this->repository->findOrFail($account->id);
	}

	/**
	 * Delete account with validation
	 */
	public function deleteAccount(Account $account): bool
	{
		// Check if account has transactions
		if ($account->transactions()->exists()) {
			throw new \Exception("Cannot delete account with existing transactions");
		}

		// If deleting default account, set another as default
		if ($account->is_default) {
			$anotherAccount = $this->repository
				->getUserAccounts($account->user)
				->where("id", "!=", $account->id)
				->first();

			if ($anotherAccount) {
				$this->setDefaultAccount($anotherAccount);
			}
		}

		return $this->repository->delete($account->id);
	}

	/**
	 * Set account as default
	 */
	public function setDefaultAccount(Account $account): bool
	{
		return $this->repository->setDefaultAccount($account);
	}

	/**
	 * Update account balance
	 */
	public function updateAccountBalance(
		Account $account,
		Money $amount,
		bool $increment = true
	): bool {
		return $this->repository->updateBalance(
			$account->id,
			$amount->getAmount()->toInt(),
			$increment
		);
	}

	/**
	 * Transfer balance between accounts
	 */
	public function transferBalance(
		Account $fromAccount,
		Account $toAccount,
		Money $amount
	): bool {
		// Validate accounts belong to same user
		if ($fromAccount->user_id !== $toAccount->user_id) {
			throw new \Exception(
				"Cannot transfer between accounts of different users"
			);
		}

		// Validate from account is not a liability
		if ($fromAccount->isLiability()) {
			throw new \Exception("Cannot transfer from liability accounts");
		}

		return $this->repository->transferBalance(
			$fromAccount->id,
			$toAccount->id,
			$amount->getAmount()->toInt()
		);
	}

	/**
	 * Recalculate account balance from transactions
	 */
	public function recalculateBalance(Account $account): bool
	{
		return DB::transaction(function () use ($account) {
			$income = $account
				->transactions()
				->where("type", "income")
				->sum("amount");

			$expense = $account
				->transactions()
				->where("type", "expense")
				->sum("amount");

			$transfersIn = $account
				->destinationTransactions()
				->where("type", "transfer")
				->sum("amount");

			$transfersOut = $account
				->transactions()
				->where("type", "transfer")
				->sum("amount");

			// Calculate new balance: initial_balance + income - expense + transfers_in - transfers_out
			$newBalance = $account->initial_balance
				->plus($income)
				->minus($expense)
				->plus($transfersIn)
				->minus($transfersOut);

			$account->balance = $newBalance;
			return $account->save();
		});
	}

	/**
	 * Get account summary
	 */
	public function getAccountSummary(User $user): array
	{
		return $this->repository->getAccountSummary($user);
	}

	/**
	 * Get account type distribution
	 */
	public function getAccountTypeDistribution(User $user): Collection
	{
		return $this->repository->getAccountTypeDistribution($user);
	}

	/**
	 * Get account analytics
	 */
	public function getAccountAnalytics(
		User $user,
		$startDate = null,
		$endDate = null
	): array {
		$startDate = $startDate ?? now()->startOfMonth();
		$endDate = $endDate ?? now()->endOfMonth();

		$accounts = $this->repository->getUserAccounts($user, [
			"is_active" => true,
		]);

		$analytics = [];

		foreach ($accounts as $account) {
			$income = $account->getIncomeForPeriod($startDate, $endDate);
			$expense = $account->getExpenseForPeriod($startDate, $endDate);
			$netFlow = $account->getNetFlowForPeriod($startDate, $endDate);

			$analytics[] = [
				"account" => $account,
				"income" => $income,
				"expense" => $expense,
				"net_flow" => $netFlow,
				"formatted_income" => $income->formatTo("id_ID"),
				"formatted_expense" => $expense->formatTo("id_ID"),
				"formatted_net_flow" => $netFlow->formatTo("id_ID"),
			];
		}

		return $analytics;
	}

	/**
	 * Validate account before operations
	 */
	public function validateAccount(Account $account, User $user): void
	{
		if ($account->user_id !== $user->id) {
			throw new \Exception("Unauthorized access to account");
		}

		if (!$account->is_active) {
			throw new \Exception("Account is not active");
		}
	}

	/**
	 * Ensure only one default account per user
	 */
	private function ensureSingleDefaultAccount(
		User $user,
		int $excludeId = null
	): void {
		$query = Account::where("user_id", $user->id)->where("is_default", true);

		if ($excludeId) {
			$query->where("id", "!=", $excludeId);
		}

		$query->update(["is_default" => false]);
	}

	/**
	 * Bulk recalculate balances for all user accounts
	 */
	public function bulkRecalculateBalances(User $user): bool
	{
		$accounts = $this->repository->getUserAccounts($user);

		foreach ($accounts as $account) {
			$this->recalculateBalance($account);
		}

		return true;
	}

	/**
	 * Get accounts for export
	 */
	public function getAccountsForExport(User $user): Collection
	{
		return $this->repository->getUserAccounts($user)->map(function ($account) {
			return [
				"id" => $account->id,
				"name" => $account->name,
				"type" => $account->type->label(),
				"balance" => $account->formatted_balance,
				"initial_balance" => $account->formatted_initial_balance,
				"currency" => $account->currency,
				"account_number" => $account->account_number,
				"bank_name" => $account->bank_name,
				"is_active" => $account->is_active ? "Yes" : "No",
				"is_default" => $account->is_default ? "Yes" : "No",
				"created_at" => $account->created_at->format("Y-m-d H:i:s"),
				"updated_at" => $account->updated_at->format("Y-m-d H:i:s"),
			];
		});
	}
}
