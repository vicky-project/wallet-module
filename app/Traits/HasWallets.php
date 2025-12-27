<?php

namespace Modules\Wallet\Traits;

use Modules\Wallet\Models\Wallet;

trait HasWallets
{
	public function wallets()
	{
		return $this->hasMany(Wallet::class, "user_id");
	}

	public function defaultWallet()
	{
		return $this->wallets()
			->where("is_default", true)
			->first();
	}

	public function transactions()
	{
		return $this->hasMany(\Modules\Wallet\Models\Transaction::class, "user_id");
	}

	public function createWallet(array $data)
	{
		if (!isset($data["is_default"])) {
			$data["is_default"] = $this->wallets()->count() === 0;
		}

		if ($data["is_default"]) {
			$this->wallets()->update(["is_default" => false]);
		}

		return $this->wallets()->create($data);
	}

	public function getTotalBalanceAttribute()
	{
		return $this->wallets()->sum("balance");
	}
}
