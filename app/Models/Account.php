<?php

namespace Modules\Wallet\Models;

use Brick\Money\Money;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
	use SoftDeletes;

	protected $fillable = [
		"user_id",
		"name",
		"account_number",
		"type",
		"description",
		"is_active",
	];

	protected $casts = [
		"is_active" => "boolean",
	];

	public function user()
	{
		return $this->belongsTo(config("auth.providers.users.model"));
	}

	public function wallets()
	{
		return $this->hasMany(Wallet::class, "account_id");
	}

	public function activeWallets()
	{
		return $this->wallets()->active();
	}

	public function transactions()
	{
		return $this->hasManyThrough(
			Transaction::class,
			Wallet::class,
			"account_id",
			"wallet_id"
		);
	}

	public function getBalanceAttribute()
	{
		$total = Money::zero($this->currency);

		foreach ($this->activeWallets() as $wallet) {
			$total = $total->plus($wallet->balance, RoundingMode::DOWN);
		}

		return $total;
	}

	public function getFormattedBalanceAttribute()
	{
		return $this->getBalanceAttribute()->formatTo("id_ID");
	}

	public function scopeActive($query)
	{
		return $query->where("is_active", true);
	}

	public function scopeByUser($query, $userId)
	{
		return $query->where("user_id", $userId);
	}
}
