<?php

namespace Modules\Wallet\Models;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Modules\Wallet\Casts\MoneyCast;
use Modules\Wallet\Enums\TransactionType;

class Transaction extends Model
{
	use HasFactory, SoftDeletes;

	protected $fillable = [
		"uuid",
		"user_id",
		"account_id",
		"to_account_id",
		"category_id",
		"type",
		"amount",
		"original_amount",
		"original_currency",
		"description",
		"notes",
		"transaction_date",
		"is_recurring",
		"recurring_template_id",
		"reference_number",
		"payment_method",
		"metadata",
	];

	protected $casts = [
		"transaction_date" => "datetime",
		"amount" => MoneyCast::class,
		"type" => TransactionType::class,
		"original_amount" => "integer",
		"metadata" => "array",
		"is_recurring" => "boolean",
	];

	protected static function boot()
	{
		parent::boot();

		static::creating(function ($transaction) {
			if (empty($transaction->uuid)) {
				$transaction->uuid = \Illuminate\Support\Str::uuid();
			}
		});

		static::created(function ($transaction) {
			// Update account balance
			$transaction->updateAccountBalance();

			// Update budget spent
			if ($transaction->type === TransactionType::EXPENSE) {
				$transaction->updateBudgetSpent();
			}

			// Flush Cache
			Cache::flush();
		});

		static::updated(function ($transaction) {
			// Recalculate balances if amount or account changed
			if (
				$transaction->isDirty(["amount", "account_id", "to_account_id", "type"])
			) {
				$transaction->recalculateBalances();
			}

			// Update budget if expense
			if (
				$transaction->type === TransactionType::EXPENSE &&
				$transaction->isDirty(["amount", "category_id"])
			) {
				$transaction->updateBudgetSpent();
			}

			// Flush Cache
			Cache::flush();
		});

		static::deleted(function ($transaction) {
			// Restore balances when deleted
			if ($transaction->type === TransactionType::INCOME) {
				$transaction->account->balance->minus(
					$transaction->amount->getMinorAmount()->toInt()
				);
			} elseif ($transaction->type === TransactionType::EXPENSE) {
				$transaction->account->balance->plus(
					$transaction->amount->getMinorAmount()->toInt()
				);
			} elseif ($transaction->type === TransactionType::TRANSFER) {
				$transaction->account->balance->plus(
					$transaction->amount->getMinorAmount()->toInt()
				);
				if ($transaction->toAccount) {
					$transaction->toAccount->balance->minus(
						$transaction->amount->getMinorAmount()->toInt()
					);
				}
			}

			// Update budget spent
			if ($transaction->type === TransactionType::EXPENSE) {
				$transaction->updateBudgetSpent(remove: true);
			}

			// Flush Cache
			Cache::flush();
		});
	}

	// Relationships
	public function user()
	{
		return $this->belongsTo(config("auth.providers.users.model"));
	}

	public function account()
	{
		return $this->belongsTo(Account::class);
	}

	public function toAccount()
	{
		return $this->belongsTo(Account::class, "to_account_id");
	}

	public function category()
	{
		return $this->belongsTo(Category::class);
	}

	public function recurringTemplate()
	{
		return $this->belongsTo(
			RecurringTransaction::class,
			"recurring_template_id"
		);
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

	public function scopeByPeriod($query, $startDate, $endDate = null)
	{
		$endDate = $endDate ?? $startDate;
		return $query->whereBetween("transaction_date", [$startDate, $endDate]);
	}

	public function scopeByAccount($query, $accountId)
	{
		return $query->where(function ($q) use ($accountId) {
			$q->where("account_id", $accountId)->orWhere("to_account_id", $accountId);
		});
	}

	// Methods
	public function updateAccountBalance()
	{
		$account = $this->account();

		switch ($this->type) {
			case TransactionType::INCOME:
				$account->update([
					"balance" => $this->account->balance->plus(
						$this->amount->getAmount()->toInt()
					),
				]);
				break;

			case TransactionType::EXPENSE:
				$account->update([
					"balance" => $this->account->balance->minus(
						$this->amount->getAmount()->toInt()
					),
				]);
				break;

			case TransactionType::TRANSFER:
				$account->update([
					"balance" => $this->account()->balance->minus(
						$this->amount->getAmount()->toInt()
					),
				]);

				if ($this->toAccount) {
					$this->toAccount()->update([
						"to_account_id" => $this->toAccount->balance->plus(
							$this->amount->getAmount()->toInt()
						),
					]);
				}
				break;
		}
	}

	public function recalculateBalances()
	{
		// Get original values before update
		$originalAmount = $this->getOriginal("amount");
		$originalType = $this->getOriginal("type");
		$originalAccountId = $this->getOriginal("account_id");
		$originalToAccountId = $this->getOriginal("to_account_id");

		// Revert old balances
		if ($originalType === TransactionType::INCOME) {
			Account::find($originalAccountId)?->decrement("balance", $originalAmount);
		} elseif ($originalType === TransactionType::EXPENSE) {
			Account::find($originalAccountId)?->increment("balance", $originalAmount);
		} elseif ($originalType === TransactionType::TRANSFER) {
			Account::find($originalAccountId)?->increment("balance", $originalAmount);
			Account::find($originalToAccountId)?->decrement(
				"balance",
				$originalAmount
			);
		}

		// Apply new balances
		$this->updateAccountBalance();
	}

	public function updateBudgetSpent($remove = false)
	{
		// Find active budget for this category in current period
		$now = $this->transaction_date ?? now();
		$budget = Budget::where("category_id", $this->category_id)
			->where("user_id", $this->user_id)
			->where("is_active", true)
			->whereDate("start_date", "<=", $now)
			->whereDate("end_date", ">=", $now)
			->first();

		if ($budget) {
			if ($remove) {
				$budget->spent->minus($this->amount->getAmount()->toInt());
			} else {
				$budget->spent->plus($this->amount->getAmount()->toInt());
			}
		}
	}

	// Accessors
	protected function formattedAmount(): Attribute
	{
		return Attribute::make(
			get: fn() => number_format(
				$this->amount->getAmount()->toInt(),
				0,
				",",
				"."
			)
		);
	}

	protected function isTransfer(): Attribute
	{
		return Attribute::make(
			get: fn() => $this->type === TransactionType::TRANSFER
		);
	}
}
