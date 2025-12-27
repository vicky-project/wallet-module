<?php

namespace Modules\Wallet\Models;

use Brick\Money\Money;
use Modules\Wallet\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
	use SoftDeletes;

	protected $fillable = [
		"transaction_code",
		"wallet_id",
		"user_id",
		"type",
		"category",
		"amount",
		"currency",
		"to_wallet_id",
		"to_account_id",
		"transaction_date",
		"payment_method",
		"reference_number",
		"description",
		"notes",
		"attachments",
		"is_reconciled",
		"reconciled_at",
		"reconciled_by",
		"meta",
	];

	protected $casts = [
		"amount" => MoneyCast::class,
		"transaction_date" => "date",
		"reconciled_at" => "datetime",
		"attachments" => "array",
		"meta" => "array",
		"is_reconciled" => "boolean",
	];

	protected static function boot()
	{
		parent::boot();

		static::creating(function ($transaction) {
			if (empty($transaction->transaction_code)) {
				$transaction->transaction_code = \Str::uuid();
			}
			if (empty($transaction->transaction_date)) {
				$transaction->transaction_date = now();
			}
		});
	}

	public function wallet()
	{
		return $this->belongsTo(Wallet::class, "wallet_id");
	}

	public function toWallet()
	{
		return $this->belongsTo(Wallet::class, "to_wallet_id");
	}

	public function user()
	{
		return $this->belongsTo(config("auth.providers.users.model"));
	}

	public function reconciledBy()
	{
		return $this->belongsTo(
			config("auth.providers.users.model"),
			"reconciled_by"
		);
	}

	public function getFormattedAmountAttribute()
	{
		return number_format($this->amount, 2);
	}

	public function isDeposit()
	{
		return $this->type === "deposit";
	}

	public function isWithdraw()
	{
		return $this->type === "withdraw";
	}

	public function isTransfer()
	{
		return $this->type === "transfer";
	}

	public function scopeDeposits($query)
	{
		return $query->where("type", "deposit");
	}

	public function scopeWithdrawals($query)
	{
		return $query->where("type", "withdraw");
	}

	public function scopeTransfers($query)
	{
		return $query->where("type", "transfer");
	}

	public function scopeBetweenDates($query, $startDate, $endDate)
	{
		return $query->whereBetween("transaction_date", [$startDate, $endDate]);
	}

	public function scopeByWallet($query, $walletId)
	{
		return $query
			->where("wallet_id", $walletId)
			->orWhere("to_wallet_id", $walletId);
	}
}
