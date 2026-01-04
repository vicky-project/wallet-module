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

		static::creating(function ($account) {
			if (!empty($account->initial_balance)) {
				$account->current_balance = $account->initial_balance;
			}
		});

		static::saving(function ($account) {
			if ($account->is_default) {
				self::where("user_id", $account->user_id)
					->where("id", "!=", $account->id)
					->update(["is_default" => false]);
			}
		});
	}

	public function user()
	{
		return $this->belongsTo(config("auth.providers.users.model"));
	}

	public function transactions()
	{
		return $this->hasMany(Transaction::class);
	}

	public function deposit(Money $amount)
	{
		$this->current_balance = $this->current_balance->plus(
			$amount,
			RoundingMode::DOWN
		);
		$this->save();
		return $this;
	}

	public function withdraw(Money $amount)
	{
		if ($this->current_balance->isLessThan($amount)) {
			throw new \Exception("Insufficient balance");
		}

		$this->current_balance = $this->current_balance->minus(
			$amount,
			RoundingMode::DOWN
		);
		$this->save();

		return $this;
	}

	public function getFormattedBalanceAttribute()
	{
		if (!$this->current_balance) {
			return 0;
		}

		return $this->current_balance->formatTo("id-ID");
	}

	public function getFormattedInitialBalanceAttribute()
	{
		if (!$this->initial_balance) {
			return 0;
		}

		return $this->initial_balance->formatTo("id_ID");
	}

	public function getTypeLabelAttribute()
	{
		return $this->type->name;
	}

	public function getBalanceChangeAttribute()
	{
		if (!$this->current_balance || !$this->initial_balance) {
			return 0;
		}

		return $this->current_balance->getAmount()->toFloat() -
			$this->initial_balance->getAmount()->toFloat();
	}

	public function getFormattedBalanceChangeAttribute()
	{
		$change = $this->balanceChange;
		$formatted = "Rp" . number_format(abs($change), 0, ",", ".");

		if ($change > 0) {
			return "+{$formatted}";
		} elseif ($change < 0) {
			return "-{$formatted}";
		}

		return $formatted;
	}

	public function getIsBalancePositiveAttribute()
	{
		return $this->balanceChange > 0;
	}

	public function scopeDefault($query)
	{
		return $query->where("is_default", true);
	}
}
