<?php

namespace Modules\Wallet\Traits;

use Modules\Wallet\Models\Account;
use Modules\Wallet\Models\Wallet;

trait HasAccounts
{
	public function accounts()
	{
		return $this->hasMany(Account::class, "user_id");
	}

	public function defaultAccount()
	{
		return $this->accounts()
			->where("is_default", true)
			->first();
	}

	public function wallets()
	{
		return $this->hasManyThrough(
			Wallet::class,
			Account::class,
			"user_id",
			"account_id"
		);
	}

	public function transactions()
	{
		return $this->hasMany(\Modules\Wallet\Models\Transaction::class, "user_id");
	}

	public function createAccount(array $data)
	{
		if (!isset($data["is_default"])) {
			$data["is_default"] = $this->accounts()->count() === 0;
		}

		if ($data["is_default"]) {
			$this->accounts()->update(["is_default" => false]);
		}

		return $this->accounts()->create($data);
	}

	public function getTotalBalanceAttribute()
	{
		return $this->accounts()->sum("balance");
	}

	public function getActiveWalletsAttribute()
	{
		return $this->wallets()
			->where("is_active", true)
			->get();
	}
}
