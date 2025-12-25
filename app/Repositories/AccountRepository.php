<?php

namespace Modules\Wallet\Repositories;

use Modules\Wallet\Models\Account;
use Illuminate\Support\Facades\Auth;

class AccountRepository
{
	protected $account;

	public function __construct(Account $account)
	{
		$this->account = $account;
	}

	public function getUserAccounts($userId = null)
	{
		$userId = $userId ?? Auth::id();

		return $this->account
			->byUser($userId)
			->active()
			->with([
				"wallets" => function ($query) {
					$query->active()->orderBy("is_default", "desc");
				},
			])
			->orderBy("is_default", "desc")
			->orderBy("created_at", "desc")
			->get();
	}

	public function createAccount(array $data)
	{
		$data["user_id"] = Auth::id();

		// If this is the first account or marked as default
		if ($data["is_default"] ?? false) {
			$this->account->byUser($data["user_id"])->update(["is_default" => false]);
		}

		return $this->account->create($data);
	}

	public function updateAccount(Account $account, array $data)
	{
		// Handle default account change
		if (isset($data["is_default"]) && $data["is_default"]) {
			$this->account
				->byUser($account->user_id)
				->where("id", "!=", $account->id)
				->update(["is_default" => false]);
		}

		$account->update($data);
		return $account;
	}

	public function deleteAccount(Account $account)
	{
		// Don't delete if there are wallets
		if ($account->wallets()->count() > 0) {
			throw new \Exception("Cannot delete account with existing wallets");
		}

		return $account->delete();
	}

	public function getAccountSummary(Account $account)
	{
		$account->load([
			"wallets" => function ($query) {
				$query->active();
			},
		]);

		$totalBalance = $account->wallets->sum("balance");
		$walletCount = $account->wallets->count();

		$recentTransactions = $account
			->transactions()
			->latest()
			->limit(10)
			->get();

		return [
			"account" => $account,
			"total_balance" => $totalBalance,
			"wallet_count" => $walletCount,
			"recent_transactions" => $recentTransactions,
		];
	}

	public function getDefaultUserAccount()
	{
		return $this->getUserAccounts()
			->filter(fn($account) => $account->is_default)
			->toArray();
	}
}
