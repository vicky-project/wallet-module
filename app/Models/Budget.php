<?php

namespace Modules\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Wallet\Enums\PeriodType;
use Modules\Wallet\Enums\TransactionType;
use Modules\Wallet\Casts\MoneyCast;
use Brick\Money\Money;
use Brick\Money\Currency;

class Budget extends Model
{
	use HasFactory, SoftDeletes;

	protected $fillable = [
		"user_id",
		"category_id",
		"name",
		"period_type",
		"period_value",
		"year",
		"start_date",
		"end_date",
		"amount",
		"spent",
		"rollover_unused",
		"rollover_limit",
		"is_active",
		"settings",
	];

	protected $casts = [
		"start_date" => "date",
		"end_date" => "date",
		"amount" => MoneyCast::class,
		"spent" => MoneyCast::class,
		"rollover_unused" => "boolean",
		"is_active" => "boolean",
		"settings" => "array",
	];

	protected $appends = [
		"remaining",
		"usage_percentage",
		"formatted_amount",
		"formatted_spent",
		"formatted_remaining",
		"period_label",
		"is_over_budget",
		"days_left",
		"daily_budget",
	];

	/**
	 * Relationship with user
	 */
	public function user(): BelongsTo
	{
		return $this->belongsTo(config("auth.providers.users.model"));
	}

	/**
	 * Relationship with category
	 */
	public function category(): BelongsTo
	{
		return $this->belongsTo(Category::class);
	}

	/**
	 * Relationship with accounts (many-to-many)
	 */
	public function accounts(): BelongsToMany
	{
		return $this->belongsToMany(
			Account::class,
			"budget_accounts"
		)->withTimestamps();
	}

	/**
	 * Scope active budgets
	 */
	public function scopeActive($query)
	{
		return $query->where("is_active", true);
	}

	/**
	 * Scope for current user
	 */
	public function scopeForUser($query, $userId)
	{
		return $query->where("user_id", $userId);
	}

	/**
	 * Scope for current period
	 */
	public function scopeCurrentPeriod($query)
	{
		return $query
			->where("start_date", "<=", now())
			->where("end_date", ">=", now());
	}

	/**
	 * Scope for specific period
	 */
	public function scopeForPeriod($query, $periodType, $periodValue, $year)
	{
		return $query
			->where("period_type", $periodType)
			->where("period_value", $periodValue)
			->where("year", $year);
	}

	/**
	 * Get remaining amount
	 */
	public function getRemainingAttribute(): int
	{
		return max(
			0,
			$this->amount->getAmount()->toInt() - $this->spent->getAmount()->toInt()
		);
	}

	/**
	 * Get usage percentage
	 */
	public function getUsagePercentageAttribute(): float
	{
		$amount = $this->amount->getAmount()->toInt();
		$spent = $this->spent->getAmount()->toInt();

		if ($amount === 0) {
			return 0;
		}

		return min(100, ($spent / $amount) * 100);
	}

	/**
	 * Get formatted amount
	 */
	public function getFormattedAmountAttribute(): string
	{
		return $this->formatMoney($this->amount->getAmount()->toInt());
	}

	/**
	 * Get formatted spent
	 */
	public function getFormattedSpentAttribute(): string
	{
		return $this->formatMoney($this->spent->getAmount()->toInt());
	}

	/**
	 * Get formatted remaining
	 */
	public function getFormattedRemainingAttribute(): string
	{
		return $this->formatMoney($this->remaining);
	}

	/**
	 * Get period label
	 */
	public function getPeriodLabelAttribute(): string
	{
		switch ($this->period_type) {
			case PeriodType::MONTHLY:
				$monthName = \DateTime::createFromFormat(
					"!m",
					$this->period_value
				)->format("F");
				return "{$monthName} {$this->year}";

			case PeriodType::WEEKLY:
				return "Week {$this->period_value}, {$this->year}";

			case PeriodType::YEARLY:
				return "Year {$this->year}";

			case PeriodType::QUARTERLY:
				$quarter = ceil($this->period_value / 3);
				return "Q{$quarter} {$this->year}";

			case PeriodType::BIWEEKLY:
				return "Bi-weekly {$this->period_value}, {$this->year}";

			default:
				return "{$this->start_date->format("d M Y")} - {$this->end_date->format(
					"d M Y"
				)}";
		}
	}

	/**
	 * Check if over budget
	 */
	public function getIsOverBudgetAttribute(): bool
	{
		return $this->spent->getAmount()->toInt() >
			$this->amount->getAmount()->toInt();
	}

	/**
	 * Get days left in budget period
	 */
	public function getDaysLeftAttribute(): int
	{
		$now = now();
		if ($now > $this->end_date) {
			return 0;
		}

		return $now->diffInDays($this->end_date) + 1;
	}

	/**
	 * Get daily budget recommendation
	 */
	public function getDailyBudgetAttribute(): float
	{
		if ($this->days_left <= 0) {
			return 0;
		}

		return $this->remaining / $this->days_left;
	}

	/**
	 * Update spent amount from transactions
	 */
	public function updateSpentAmount(): void
	{
		$query = $this->category
			->transactions()
			->expense()
			->whereBetween("transaction_date", [$this->start_date, $this->end_date]);

		// If budget has specific accounts, filter by them
		if ($this->accounts->isNotEmpty()) {
			$query->whereIn("account_id", $this->accounts->pluck("id"));
		}

		$this->spent = $query->sum("amount");
		$this->save();
	}

	/**
	 * Check if budget can rollover
	 */
	public function canRollover(): bool
	{
		if (!$this->rollover_unused) {
			return false;
		}

		if (
			$this->rollover_limit !== null &&
			$this->remaining > $this->rollover_limit
		) {
			return false;
		}

		return $this->remaining > 0;
	}

	/**
	 * Get rollover amount
	 */
	public function getRolloverAmount(): int
	{
		if (!$this->canRollover()) {
			return 0;
		}

		if ($this->rollover_limit !== null) {
			return min($this->remaining, $this->rollover_limit);
		}

		return $this->remaining;
	}

	/**
	 * Format money helper
	 */
	private function formatMoney(int $amount): string
	{
		// Assuming IDR currency, adjust as needed
		$money = new Money($amount, new Currency("IDR"));

		return "Rp " . number_format($money->getAmount()->toInt(), 0, ",", ".");
	}

	/**
	 * Check if date is within budget period
	 */
	public function isDateInPeriod(\DateTimeInterface $date): bool
	{
		$date = \Carbon\Carbon::parse($date);
		return $date->between($this->start_date, $this->end_date);
	}

	/**
	 * Get next period budget
	 */
	public function getNextPeriod(): ?self
	{
		$nextDate = $this->end_date->copy()->addDay();

		return self::where("user_id", $this->user_id)
			->where("category_id", $this->category_id)
			->where("start_date", "<=", $nextDate)
			->where("end_date", ">=", $nextDate)
			->where("is_active", true)
			->first();
	}

	/**
	 * Get previous period budget
	 */
	public function getPreviousPeriod(): ?self
	{
		$prevDate = $this->start_date->copy()->subDay();

		return self::where("user_id", $this->user_id)
			->where("category_id", $this->category_id)
			->where("start_date", "<=", $prevDate)
			->where("end_date", ">=", $prevDate)
			->where("is_active", true)
			->first();
	}

	/**
	 * Get suggested next budget amount
	 */
	public function getSuggestedNextAmount(): int
	{
		$suggested = $this->amount;

		// Adjust based on rollover
		if ($this->rollover_unused) {
			$suggested += $this->getRolloverAmount();
		}

		// Consider average spending
		$previousBudgets = self::where("user_id", $this->user_id)
			->where("category_id", $this->category_id)
			->where("id", "!=", $this->id)
			->where("end_date", "<", $this->start_date)
			->orderBy("end_date", "desc")
			->limit(3)
			->get();

		if ($previousBudgets->isNotEmpty()) {
			$averageSpent = $previousBudgets->avg("spent");
			$suggested = max($suggested, (int) round($averageSpent * 1.1)); // 10% buffer
		}

		return $suggested;
	}
}
