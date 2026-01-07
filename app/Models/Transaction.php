<?php

namespace Modules\Wallet\Models;

use Modules\Wallet\Enums\TransactionType;
use Modules\Wallet\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
	protected $fillable = [
		"user_id",
		"category_id",
		"account_id",
		"title",
		"description",
		"amount",
		"type",
		"transaction_date",
		"payment_method",
		"reference_number",
		"is_recurring",
		"recurring_period",
		"recurring_end_date",
		"is_verified",
	];

	protected $casts = [
		"transaction_date" => "datetime",
		"amount" => MoneyCast::class,
		"is_recurring" => "boolean",
		"is_verified" => "boolean",
		"recurring_end_date" => "date",
		"type" => TransactionType::class,
	];

	protected static function boot()
	{
		parent::boot();

		static::saving(function ($transaction) {
			if (!$transaction->is_recurring) {
				self::where("user_id", $transaction->user_id)
					->where("id", $transaction->id)
					->where("account_id", $transaction->account_id)
					->update([
						"recurring_period" => null,
						"recurring_end_date" => null,
					]);
			}
		});
	}

	// Relationships
	public function user()
	{
		return $this->belongsTo(config("auth.providers.users.model"));
	}

	public function category()
	{
		return $this->belongsTo(Category::class);
	}

	public function account()
	{
		return $this->belongsTo(Account::class);
	}

	// Scopes
	public function scopeIncome($query)
	{
		return $query->where("type", TransactionType::INCOME);
	}

	public function scopeExpense($query)
	{
		return $query->where("type", TransactionType::EXPENSE);
	}

	public function scopeTransfer($query)
	{
		return $query->where("type", TransactionType::TRANSFER);
	}

	public function scopeThisMonth($query)
	{
		return $query
			->whereMonth("transaction_date", date("m"))
			->whereYear("transaction_date", date("Y"));
	}

	public function scopeRecent($query, $limit = 10)
	{
		return $query
			->orderBy("transaction_date", "desc")
			->orderBy("created_at", "desc")
			->limit($limit);
	}
}
