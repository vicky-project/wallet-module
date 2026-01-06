<?php

namespace Modules\Wallet\Models;

use Modules\Wallet\Casts\MoneyCast;
use Modules\Wallet\Enums\CategoryType;
use Modules\Wallet\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Budget extends Model
{
	use HasFactory, SoftDeletes;

	protected $fillable = [
		"user_id",
		"category_id",
		"amount",
		"month",
		"year",
		"spent", // Ini akan diupdate dari transaksi expense pada kategori ini
		"is_active", // Status aktif/tidak aktif
	];

	protected $casts = [
		"amount" => MoneyCast::class,
		"spent" => MoneyCast::class,
		"month" => "integer",
		"year" => "integer",
		"is_active" => "boolean",
		"created_at" => "datetime",
		"updated_at" => "datetime",
		"deleted_at" => "datetime",
	];

	// Array of month names
	const MONTH_NAMES = [
		1 => "Januari",
		2 => "Februari",
		3 => "Maret",
		4 => "April",
		5 => "Mei",
		6 => "Juni",
		7 => "Juli",
		8 => "Agustus",
		9 => "September",
		10 => "Oktober",
		11 => "November",
		12 => "Desember",
	];

	/**
	 * Relationship with User
	 */
	public function user()
	{
		return $this->belongsTo(config("auth.providers.users.model"));
	}

	/**
	 * Relationship with Category (hanya kategori expense)
	 */
	public function category()
	{
		return $this->belongsTo(Category::class)->where(
			"type",
			CategoryType::EXPENSE
		);
	}

	/**
	 * Calculate remaining amount
	 */
	public function getRemainingAttribute()
	{
		return max(
			0,
			$this->amount->getAmount()->toInt() - $this->spent->getAmount()->toInt()
		);
	}

	/**
	 * Calculate percentage spent - INI YANG DIPERBAIKI
	 * Persentase dihitung dari spent/amount
	 */
	public function getPercentageAttribute()
	{
		$amount = $this->amount->getAmount()->toInt();
		$spent = $this->spent->getAmount()->toInt();

		if ($amount == 0) {
			return 0;
		}

		return min(100, round(($spent / $amount) * 100, 2));
	}

	/**
	 * Get formatted amount
	 */
	public function getFormattedAmountAttribute()
	{
		return "Rp " .
			number_format($this->amount->getAmount()->toInt() / 100, 0, ",", ".");
	}

	/**
	 * Get formatted spent amount
	 */
	public function getFormattedSpentAttribute()
	{
		return "Rp " .
			number_format($this->spent->getAmount()->toInt() / 100, 0, ",", ".");
	}

	/**
	 * Get formatted remaining amount
	 */
	public function getFormattedRemainingAttribute()
	{
		return "Rp " . number_format($this->remaining / 100, 0, ",", ".");
	}

	/**
	 * Get month name
	 */
	public function getMonthNameAttribute()
	{
		return self::MONTH_NAMES[$this->month] ?? "Bulan Tidak Diketahui";
	}

	/**
	 * Get budget period (e.g., "Januari 2024")
	 */
	public function getPeriodAttribute()
	{
		return $this->month_name . " " . $this->year;
	}

	/**
	 * Check if budget is exceeded
	 */
	public function getIsExceededAttribute()
	{
		return $this->spent->getAmount()->toInt() >
			$this->amount->getAmount()->toInt();
	}

	/**
	 * Get budget status
	 */
	public function getStatusAttribute()
	{
		$percentage = $this->percentage;

		if ($percentage >= 100) {
			return "exceeded";
		} elseif ($percentage >= 80) {
			return "warning";
		} elseif ($percentage >= 50) {
			return "moderate";
		} else {
			return "good";
		}
	}

	/**
	 * Get status color
	 */
	public function getStatusColorAttribute()
	{
		return match ($this->status) {
			"exceeded" => "danger",
			"warning" => "warning",
			"moderate" => "info",
			"good" => "success",
			default => "secondary",
		};
	}

	/**
	 * Scope for active budgets
	 */
	public function scopeActive($query)
	{
		return $query->where("is_active", true);
	}

	/**
	 * Scope for current month budget
	 */
	public function scopeCurrentMonth($query)
	{
		return $query->where("month", date("m"))->where("year", date("Y"));
	}

	/**
	 * Scope for specific month and year
	 */
	public function scopeForPeriod($query, $month, $year)
	{
		return $query->where("month", $month)->where("year", $year);
	}

	/**
	 * Update spent amount from transactions - INI YANG DIPERBAIKI
	 * Hanya hitung dari transaksi expense pada kategori ini
	 */
	public function updateSpentAmount()
	{
		// HITUNG TOTAL PENGELUARAN PADA KATEGORI INI
		$totalSpent = Transaction::where("user_id", $this->user_id)
			->where("category_id", $this->category_id)
			->where("type", TransactionType::EXPENSE) // HANYA TRANSAKSI EXPENSE
			->whereMonth("transaction_date", $this->month)
			->whereYear("transaction_date", $this->year)
			->sum("amount");

		// Update spent amount
		$this->spent = $totalSpent;
		$this->save();

		return $this;
	}

	/**
	 * Check if budget is for current period
	 */
	public function getIsCurrentAttribute()
	{
		return $this->month == date("m") && $this->year == date("Y");
	}

	/**
	 * Calculate average daily budget
	 */
	public function getDailyBudgetAttribute()
	{
		$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $this->month, $this->year);
		$remainingDays = $daysInMonth - date("j") + 1;

		return $this->remaining / max(1, $remainingDays);
	}

	/**
	 * Get formatted daily budget
	 */
	public function getFormattedDailyBudgetAttribute()
	{
		return "Rp " . number_format($this->daily_budget / 100, 0, ",", ".");
	}

	/**
	 * Get budget progress data for charts
	 */
	public function getProgressDataAttribute()
	{
		$spent = $this->spent->getAmount()->toInt() / 100;
		$amount = $this->amount->getAmount()->toInt() / 100;
		$remaining = max(0, $amount - $spent);

		return [
			"spent" => $spent,
			"remaining" => $remaining,
			"amount" => $amount,
			"percentage" => $this->percentage,
		];
	}
}
