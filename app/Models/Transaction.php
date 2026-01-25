<?php

namespace Modules\Wallet\Models;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
	}

	public static function booted()
	{
		parent::booted();

		static::created(function ($transaction) {
			// Update account balance
			$transaction->updateAccountBalance();

			// Update budget spent
			if ($transaction->type === TransactionType::EXPENSE) {
				$transaction->updateBudgetSpent();
			}
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
		});

		static::deleted(function ($transaction) {
			// Restore balances when deleted
			if ($transaction->type === TransactionType::INCOME) {
				$account = $transaction->account()->first();
				$account->balance = $account->balance->minus(
					$transaction->amount->getMinorAmount()->toInt()
				);
				$account->save();
			} elseif ($transaction->type === TransactionType::EXPENSE) {
				$account = $transaction->account()->first();
				$account->balance = $account->balance->plus(
					$transaction->amount->getMinorAmount()->toInt()
				);
				$account->save();
			} elseif ($transaction->type === TransactionType::TRANSFER) {
				$account = $transaction->account()->first();
				$account->balance = $account->balance->plus(
					$transaction->amount->getMinorAmount()->toInt()
				);

				$account->save();
				if ($transaction->toAccount) {
					$toAccount = $transaction->toAccount()->first();
					$toAccount->balance = $transaction->toAccount->minus(
						$transaction->amount->getMinorAmount()->toInt()
					);

					$toAccount->save();
				}
			}

			// Update budget spent
			if ($transaction->type === TransactionType::EXPENSE) {
				$transaction->updateBudgetSpent();
			}
		});

		// Flush Cache
		Cache::flush();
		Artisan::call("optimize:clear");
	}

	// Relationships
	public function user(): BelongsTo
	{
		return $this->belongsTo(config("auth.providers.users.model"));
	}

	public function account(): BelongsTo
	{
		return $this->belongsTo(Account::class);
	}

	public function toAccount(): BelongsTo
	{
		return $this->belongsTo(Account::class, "to_account_id");
	}

	public function category(): BelongsTo
	{
		return $this->belongsTo(Category::class);
	}

	public function recurringTemplate(): BelongsTo
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

	public function scopeRecurring($query)
	{
		return $query->where("is_recurring", true);
	}

	public function scopeNonRecurring($query)
	{
		return $query->where("is_recurring", false);
	}

	public function scopeFromRecurringTemplate($query, $templateId)
	{
		return $query->where("recurring_template_id", $templateId);
	}

	// Helper methods
	public function isFromRecurring(): bool
	{
		return !is_null($this->recurring_template_id);
	}

	// Methods
	public function updateAccountBalance()
	{
		DB::transaction(function () {
			$amount = $this->amount->getAmount()->toInt();

			switch ($this->type) {
				case TransactionType::INCOME:
					$this->handleIncome($amount);
					break;

				case TransactionType::EXPENSE:
					$this->handleExpense($amount);
					break;

				case TransactionType::TRANSFER:
					$this->handleTransfer($amount);
					break;
			}
		});
	}

	private function handleIncome($amount)
	{
		$account = $this->account()
			->lockForUpdate()
			->first();

		if (!$account) {
			return;
		}

		$account->incrementBalance($amount);
	}

	private function handleExpense($amount)
	{
		$account = $this->account()
			->lockForUpdate()
			->first();

		if (!$account) {
			return;
		}

		if ($account->balance->getAmount()->toInt() < $amount) {
			throw new \Exception("Insufficient balance");
		}

		$account->decrementBalance($amount);
	}

	private function handleTransfer($amount)
	{
		$fromAccount = $this->account()
			->lockForUpdate()
			->first();
		$toAccount = $this->toAccount()
			->lockForUpdate()
			->first();

		if (!$fromAccount || !$toAccount) {
			throw new \Exception("Account not found.");
		}

		if ($fromAccount->id === $toAccount->id) {
			throw new \Exception("Can not transfer to the same account");
		}

		if ($fromAccount->balance->getAmount()->toInt() < $amount) {
			throw new \Exception("Insufficient balance");
		}

		$fromAccount->decrementBalance($amount);
		$toAccount->incrementBalance($amount);
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

	public function updateBudgetSpent()
	{
		// Find active budget for this category in current period
		$now = $this->transaction_date ?? now();
		$budget = Budget::where("category_id", $this->category_id)
			->forUser($this->user_id)
			->active()
			->whereDate("start_date", "<=", $now)
			->whereDate("end_date", ">=", $now)
			->first();

		if ($budget) {
			$budget->updateSpentAmount();
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
