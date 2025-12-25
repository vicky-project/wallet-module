<?php

namespace Modules\Wallet\Repositories;

use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Entities\Account;
use Illuminate\Support\Facades\Auth;
use Brick\Money\Money;
use Brick\Math\RoundingMode;

class WalletRepository
{
	protected $wallet;

	public function __construct(Wallet $wallet)
	{
		$this->wallet = $wallet;
	}

	public function getUserWallets(array $filters = [])
	{
		$query = $this->wallet
			->whereHas("account", function ($q) {
				$q->where("user_id", Auth::id());
			})
			->with("account");

		if (isset($filters["account_id"])) {
			$query->where("account_id", $filters["account_id"]);
		}

		if (isset($filters["is_active"])) {
			$query->where("is_active", (bool) $filters["is_active"]);
		}

		if (isset($filters["type"])) {
			$query->where("type", $filters["type"]);
		}

		if (isset($filters["currency"])) {
			$query->where("currency", $filters["currency"]);
		}

		return $query
			->orderBy("is_default", "desc")
			->orderBy("created_at", "desc")
			->get();
	}

	public function createWallet(Account $account, array $data)
	{
		$data["account_id"] = $account->id;

		// If this wallet is set as default, unset default for other wallets in the same account
		if (isset($data["is_default"]) && $data["is_default"]) {
			$this->wallet
				->where("account_id", $account->id)
				->update(["is_default" => false]);
		}

		// Set initial balance as current balance
		if (isset($data["initial_balance"])) {
			$data["balance"] = $data["initial_balance"];
		}

		return $this->wallet->create($data);
	}

	public function updateWallet(Wallet $wallet, array $data)
	{
		// Handle default wallet change
		if (isset($data["is_default"]) && $data["is_default"]) {
			$this->wallet
				->where("account_id", $wallet->account_id)
				->where("id", "!=", $wallet->id)
				->update(["is_default" => false]);
		}

		// Don't allow updating balance directly
		unset($data["balance"]);

		$wallet->update($data);
		return $wallet;
	}

	public function deleteWallet(Wallet $wallet)
	{
		// Check if there are transactions
		if ($wallet->transactions()->count() > 0) {
			throw new \Exception("Cannot delete wallet with existing transactions");
		}

		return $wallet->delete();
	}

	public function getWalletSummary(Wallet $wallet)
	{
		$wallet->loadCount([
			"transactions as total_transactions",
			"transactions as completed_transactions" => function ($query) {
				$query->where("status", "completed");
			},
			"transactions as pending_transactions" => function ($query) {
				$query->where("status", "pending");
			},
		]);

		$monthlySummary = $wallet
			->transactions()
			->where("status", "completed")
			->whereMonth("transaction_date", now()->month)
			->selectRaw(
				'
                type,
                COUNT(*) as count,
                SUM(net_amount) as total_amount
            '
			)
			->groupBy("type")
			->get();

		return [
			"wallet" => $wallet,
			"monthly_summary" => $monthlySummary,
		];
	}
}
