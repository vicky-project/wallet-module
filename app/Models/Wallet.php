<?php

namespace Modules\Wallet\Models;

use Brick\Money\Money;
use Brick\Math\RoundingMode;
use Modules\Wallet\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Wallet extends Model
{
	use SoftDeletes;

	protected $fillable = [
		"account_id",
		"name",
		"wallet_code",
		"type",
		"balance",
		"initial_balance",
		"currency",
		"is_active",
		"is_default",
		"description",
		"meta",
	];

	protected $casts = [
		"balance" => MoneyCast::class,
		"initial_balance" => MoneyCast::class,
		"is_active" => "boolean",
		"is_default" => "boolean",
		"metadata" => "array",
	];

	protected static function boot()
	{
		parent::boot();

		static::creating(function ($wallet) {
			if (empty($wallet->wallet_code)) {
				$wallet->wallet_code = "WLT" . strtoupper(uniqid());
			}
		});
	}

	public function account()
	{
		return $this->belongsTo(Account::class, "account_id");
	}

	public function transactions()
	{
		return $this->hasMany(Transaction::class, "wallet_id");
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

	public function scopeActive($query)
	{
		return $query->where("is_active", true);
	}

	public function scopeByAccount($query, $accountId)
	{
		return $query->where("account_id", $accountId);
	}

	public function scopeDefault($query)
	{
		return $query->where("is_default", true);
	}
}
