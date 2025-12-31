<?php

namespace Modules\Wallet\Models;

use Brick\Money\Money;
use Brick\Math\RoundingMode;
use Modules\Wallet\Casts\MoneyCast;
use Modules\Wallet\Enums\AccountType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
	use SoftDeletes;

	protected $fillable = [
		"user_id",
		"name",
		"type",
		"account_number",
		"bank_name",
		"current_balance",
		"initial_balance",
		"is_default",
	];

	protected $casts = [
		"type" => AccountType::class,
		"current_balance" => MoneyCast::class,
		"initial_balance" => MoneyCast::class,
		"is_default" => "boolean",
	];

	protected static function boot()
	{
		parent::boot();

		static::creating(function ($wallet) {
			if (!empty($wallet->initial_balance)) {
				$wallet->current_balance = $this->initial_balance;
			}
		});
	}

	public function transactions()
	{
		return $this->hasMany(Transaction::class);
	}

	public function incomingTransfers()
	{
		return $this->hasMany(Transaction::class, "to_wallet_id");
	}

	public function deposit(Money $amount)
	{
		$this->balance = $this->balance->plus($amount, RoundingMode::DOWN);
		$this->save();
		return $this;
	}

	public function withdraw(Money $amount)
	{
		if ($this->balance->isLessThan($amount)) {
			throw new \Exception("Insufficient balance");
		}

		$this->balance = $this->balance->minus($amount, RoundingMode::DOWN);
		$this->save();

		return $this;
	}

	public function transferTo(Wallet $target, Money $amount)
	{
		if ($this->balance->isLessThan($amount)) {
			throw new \Exception("Insufficient balance");
		}
		$this->balance = $this->balance->minus($amount, RoundingMode::DOWN);
		$this->save();

		$target->balance = $target->balance->plus($amount, RoundingMode::DOWN);
		$target->save();

		return ["source" => $this, "target" => $target, "amount" => $amount];
	}

	public function getFormattedBalanceAttribute()
	{
		return $this->balance->formatTo("id_ID");
	}

	public function getFormattedInitialBalanceAttribute()
	{
		return $this->initial_balance->formatTo("id_ID");
	}

	public function scopeDefault($query)
	{
		return $query->where("is_default", true);
	}
}
