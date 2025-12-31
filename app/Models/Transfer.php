<?php

namespace Modules\Wallet\Models;

use Brick\Money\Money;
use Brick\Math\RoundingMode;
use Modules\Wallet\Casts\MoneyCast;
use Modules\Wallet\Enums\AccountType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transfer extends Model
{
	use HasFactory, SoftDeletes;

	protected $fillable = [
		"user_id",
		"from_account_id",
		"to_account_id",
		"amount",
		"transfer_date",
		"description",
		"fee",
	];

	protected $casts = [
		"amount" => MoneyCast::class,
		"fee" => MoneyCast::class,
		"transfer_date" => "date",
		"created_at" => "datetime",
		"updated_at" => "datetime",
		"deleted_at" => "datetime",
	];

	/**
	 * Relationship with User
	 */
	public function user()
	{
		return $this->belongsTo(config("auth.providers.users.model"));
	}

	/**
	 * Relationship with source account
	 */
	public function fromAccount()
	{
		return $this->belongsTo(Account::class, "from_account_id");
	}

	/**
	 * Relationship with destination account
	 */
	public function toAccount()
	{
		return $this->belongsTo(Account::class, "to_account_id");
	}

	/**
	 * Calculate net amount (amount - fee)
	 */
	public function getNetAmountAttribute()
	{
		return $this->amount->minus($this->fee, RoundingMode::DOWN);
	}

	/**
	 * Get formatted amount
	 */
	public function getFormattedAmountAttribute()
	{
		return "Rp " . number_format($this->amount, 0, ",", ".");
	}

	/**
	 * Get formatted fee
	 */
	public function getFormattedFeeAttribute()
	{
		return "Rp " . number_format($this->fee, 0, ",", ".");
	}

	/**
	 * Get formatted net amount
	 */
	public function getFormattedNetAmountAttribute()
	{
		return "Rp " . number_format($this->net_amount, 0, ",", ".");
	}

	/**
	 * Get transfer type
	 */
	public function getTransferTypeAttribute()
	{
		if ($this->fromAccount && $this->toAccount) {
			return $this->fromAccount->type . "_to_" . $this->toAccount->type;
		}

		return "unknown";
	}

	/**
	 * Check if transfer involves same account type
	 */
	public function getIsInternalTransferAttribute()
	{
		return $this->fromAccount->type === $this->toAccount->type;
	}

	/**
	 * Check if transfer is between bank accounts
	 */
	public function getIsBankTransferAttribute()
	{
		return $this->fromAccount->type === AccountType::BANK &&
			$this->toAccount->type === AccountType::BANK;
	}

	/**
	 * Check if transfer is from bank to e-wallet
	 */
	public function getIsBankToEwalletAttribute()
	{
		return $this->fromAccount->type === AccountType::BANK &&
			$this->toAccount->type === AccountType::EWALLET;
	}

	/**
	 * Check if transfer is from e-wallet to bank
	 */
	public function getIsEwalletToBankAttribute()
	{
		return $this->fromAccount->type === AccountType::EWALLET &&
			$this->toAccount->type === AccountType::Bank;
	}

	/**
	 * Get transfer status icon
	 */
	public function getStatusIconAttribute()
	{
		if ($this->isInternalTransfer) {
			return "bi-arrow-left-right";
		}

		if ($this->fromAccount->type === AccountType::CASH) {
			return "bi-cash-coin";
		}

		if ($this->toAccount->type === AccountType::CASH) {
			return "bi-wallet";
		}

		return "bi-arrow-right-circle";
	}

	/**
	 * Get transfer status color
	 */
	public function getStatusColorAttribute()
	{
		if ($this->fee > 0) {
			return "warning";
		}

		if ($this->isBankTransfer) {
			return "primary";
		}

		if ($this->isBankToEwallet || $this->isEwalletToBank) {
			return "info";
		}

		return "secondary";
	}

	/**
	 * Scope for transfers in specific period
	 */
	public function scopeForPeriod($query, $startDate, $endDate)
	{
		return $query->whereBetween("transfer_date", [$startDate, $endDate]);
	}

	/**
	 * Scope for transfers from specific account
	 */
	public function scopeFromAccount($query, $accountId)
	{
		return $query->where("from_account_id", $accountId);
	}

	/**
	 * Scope for transfers to specific account
	 */
	public function scopeToAccount($query, $accountId)
	{
		return $query->where("to_account_id", $accountId);
	}

	/**
	 * Scope for transfers involving specific account
	 */
	public function scopeInvolvingAccount($query, $accountId)
	{
		return $query->where(function ($q) use ($accountId) {
			$q->where("from_account_id", $accountId)->orWhere(
				"to_account_id",
				$accountId
			);
		});
	}

	/**
	 * Execute the transfer (update account balances)
	 */
	public function execute()
	{
		\DB::transaction(function () {
			// Deduct from source account
			$this->fromAccount->decrement("current_balance", $this->amount);

			// Add to destination account (minus fee)
			$this->toAccount->increment("current_balance", $this->net_amount);

			$this->save();
		});

		return $this;
	}

	/**
	 * Reverse the transfer (rollback account balances)
	 */
	public function reverse()
	{
		\DB::transaction(function () {
			// Return to source account
			$this->fromAccount->increment("current_balance", $this->amount);

			// Deduct from destination account
			$this->toAccount->decrement("current_balance", $this->net_amount);

			$this->delete();
		});

		return true;
	}

	/**
	 * Get transfer summary for display
	 */
	public function getSummaryAttribute()
	{
		return sprintf(
			"Transfer dari %s ke %s",
			$this->fromAccount->name,
			$this->toAccount->name
		);
	}

	/**
	 * Check if transfer is recent (within 24 hours)
	 */
	public function getIsRecentAttribute()
	{
		return $this->created_at->diffInHours(now()) <= 24;
	}

	/**
	 * Get transfer fee percentage
	 */
	public function getFeePercentageAttribute()
	{
		if ($this->amount == 0) {
			return 0;
		}

		return round(($this->fee / $this->amount) * 100, 2);
	}
}
