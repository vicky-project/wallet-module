<?php

namespace Modules\Wallet\Models;

use Brick\Money\Money;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Wallet\Helpers\Helper;
use Modules\Wallet\Casts\MoneyCast;
use Modules\Wallet\Enums\AccountType;
use Modules\Wallet\Enums\TransactionType;

class Account extends Model
{
	use SoftDeletes;

	protected $fillable = [
		"user_id",
		"name",
		"type",
		"balance",
		"initial_balance",
		"currency",
		"account_number",
		"bank_name",
		"color",
		"icon",
		"is_active",
		"is_default",
		"notes",
	];

	protected $casts = [
		"type" => AccountType::class,
		"balance" => MoneyCast::class,
		"initial_balance" => MoneyCast::class,
		"is_active" => "boolean",
		"is_default" => "boolean",
		"created_at" => "datetime",
		"updated_at" => "datetime",
		"deleted_at" => "datetime",
	];

	protected $attributes = [
		"type" => "cash",
		"currency" => "IDR",
		"color" => "#3490dc",
		"icon" => "bi-wallet",
		"is_active" => true,
		"is_default" => false,
	];

	public static function boot()
	{
		parent::boot();

		static::creating(function ($account) {
			// Ensure only one default account per user
			if ($account->is_default) {
				self::where("user_id", $account->user_id)
					->where("is_default", true)
					->update(["is_default" => false]);
			}
		});

		static::updating(function ($account) {
			// Ensure only one default account per user
			if ($account->is_default) {
				self::where("user_id", $account->user_id)
					->where("is_default", true)
					->where("id", "!=", $account->id)
					->update(["is_default" => false]);
			}
		});
	}

	protected static function booted()
	{
		parent::booted();

		static::created(function ($account) {
			Cache::flush();
		});

		static::updated(function ($account) {
			Cache::flush();
		});

		static::deleted(function ($account) {
			Cache::flush();
		});
	}

	/**
	 * Relationship with User
	 */
	public function user()
	{
		return $this->belongsTo(config("auth.providers.users.model"));
	}

	/**
	 * Relationship with Transactions (as source account)
	 */
	public function transactions()
	{
		return $this->hasMany(Transaction::class);
	}

	/**
	 * Relationship with Transactions (as destination account for transfers)
	 */
	public function destinationTransactions()
	{
		return $this->hasMany(Transaction::class, "to_account_id");
	}

	/**
	 * Scope for active accounts
	 */
	public function scopeActive($query)
	{
		return $query->where("is_active", true);
	}

	/**
	 * Scope for default account
	 */
	public function scopeDefault($query)
	{
		return $query->where("is_default", true);
	}

	/**
	 * Scope for specific type
	 */
	public function scopeType($query, $type)
	{
		return $query->where("type", $type);
	}

	/**
	 * Scope for user accounts
	 */
	public function scopeForUser($query, $userId)
	{
		return $query->where("user_id", $userId);
	}

	/**
	 * Check if account is liability (credit card or loan)
	 */
	public function isLiability(): bool
	{
		return in_array($this->type, [AccountType::CREDIT_CARD, AccountType::CASH]);
	}

	/**
	 * Check if account is asset
	 */
	public function isAsset(): bool
	{
		return !$this->isLiability();
	}

	/**
	 * Get formatted balance
	 */
	public function getFormattedBalanceAttribute(): string
	{
		return $this->balance->formatTo("id_ID");
	}

	/**
	 * Get formatted initial balance
	 */
	public function getFormattedInitialBalanceAttribute(): string
	{
		return $this->initial_balance->formatTo("id_ID");
	}

	/**
	 * Get total income for period
	 */
	public function getIncomeForPeriod($startDate, $endDate)
	{
		$cacheKey = Helper::generateCacheKey("account_income_period", [
			"start_date" => $startDate,
			"end_date" => $endDate,
		]);

		return Cache::remember(
			$cacheKey,
			config("wallet.cache_ttl"),
			function () use ($startDate, $endDate) {
				return $this->transactions()
					->where("type", TransactionType::INCOME)
					->whereBetween("transaction_date", [$startDate, $endDate])
					->sum("amount");
			}
		);
	}

	/**
	 * Get total expense for period
	 */
	public function getExpenseForPeriod($startDate, $endDate)
	{
		$cacheKey = Helper::generateCacheKey("account_expense_period", [
			"start_date" => $startDate,
			"end_date" => $endDate,
		]);

		return Cache::remember(
			$cacheKey,
			config("wallet.cache_ttl"),
			function () use ($startDate, $endDate) {
				return $this->transactions()
					->where("type", TransactionType::EXPENSE)
					->whereBetween("transaction_date", [$startDate, $endDate])
					->sum("amount");
			}
		);
	}

	/**
	 * Get net flow for period (income - expense)
	 */
	public function getNetFlowForPeriod($startDate, $endDate)
	{
		$income = Money::ofMinor(
			$this->getIncomeForPeriod($startDate, $endDate),
			$this->currency
		);
		$expense = $this->getExpenseForPeriod($startDate, $endDate);
		return $income->minus($expense);
	}

	public function incrementBalance($amount)
	{
		$newBalance = $this->balance
			->plus($amount)
			->getAmount()
			->toInt();

		$this->update(["balance" => $newBalance]);
		$this->refresh();
	}

	public function decrementBalance($amount)
	{
		$newBalance = $this->balance
			->minus($amount)
			->getAmount()
			->toInt();

		$this->update(["balance" => $newBalance]);
		$this->refresh();
	}
}
