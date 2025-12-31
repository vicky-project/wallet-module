<?php

namespace Modules\Wallet\Models;

use Modules\Wallet\Casts\MoneyCast;
use Modules\Wallet\Enums\PriorityGoalType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SavingGoal extends Model
{
	use HasFactory, SoftDeletes;

	protected $fillable = [
		"user_id",
		"name",
		"target_amount",
		"current_amount",
		"target_date",
		"priority",
		"is_completed",
		"completed_at",
	];

	protected $casts = [
		"target_amount" => MoneyCast::class,
		"current_amount" => MoneyCast::class,
		"target_date" => "date",
		"completed_at" => "datetime",
		"is_completed" => "boolean",
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
	 * Calculate progress percentage
	 */
	public function getProgressPercentageAttribute()
	{
		if ($this->target_amount == 0) {
			return 0;
		}

		$percentage = ($this->current_amount / $this->target_amount) * 100;
		return min(100, round($percentage, 2));
	}

	/**
	 * Calculate remaining amount
	 */
	public function getRemainingAmountAttribute()
	{
		return max(0, $this->target_amount - $this->current_amount);
	}

	/**
	 * Calculate days remaining
	 */
	public function getDaysRemainingAttribute()
	{
		$now = now();
		$targetDate = $this->target_date;

		if ($targetDate <= $now) {
			return 0;
		}

		return $now->diffInDays($targetDate);
	}

	/**
	 * Calculate required daily saving
	 */
	public function getRequiredDailySavingAttribute()
	{
		if ($this->days_remaining <= 0) {
			return $this->remaining_amount;
		}

		return $this->remaining_amount / $this->days_remaining;
	}

	/**
	 * Check if goal is on track
	 */
	public function getIsOnTrackAttribute()
	{
		if ($this->is_completed || $this->days_remaining <= 0) {
			return $this->progress_percentage >= 100;
		}

		$elapsedDays = now()->diffInDays($this->created_at->startOfDay());
		$expectedProgress =
			($elapsedDays / ($elapsedDays + $this->days_remaining)) * 100;

		return $this->progress_percentage >= $expectedProgress;
	}

	/**
	 * Get formatted target amount
	 */
	public function getFormattedTargetAmountAttribute()
	{
		return "Rp " . number_format($this->target_amount, 0, ",", ".");
	}

	/**
	 * Get formatted current amount
	 */
	public function getFormattedCurrentAmountAttribute()
	{
		return "Rp " . number_format($this->current_amount, 0, ",", ".");
	}

	/**
	 * Get formatted remaining amount
	 */
	public function getFormattedRemainingAmountAttribute()
	{
		return "Rp " . number_format($this->remaining_amount, 0, ",", ".");
	}

	/**
	 * Get formatted required daily saving
	 */
	public function getFormattedRequiredDailySavingAttribute()
	{
		return "Rp " . number_format($this->required_daily_saving, 0, ",", ".");
	}

	/**
	 * Get priority label
	 */
	public function getPriorityLabelAttribute()
	{
		$labels = [
			PriorityGoalType::LOW => "Rendah",
			PriorityGoalType::MEDIUM => "Sedang",
			PriorityGoalType::HIGH => "Tinggi",
		];

		return $labels[$this->priority] ?? "Tidak Diketahui";
	}

	/**
	 * Add amount to current savings
	 */
	public function addAmount($amount)
	{
		$this->current_amount += $amount;

		// Auto complete if target reached
		if ($this->current_amount >= $this->target_amount) {
			$this->current_amount = $this->target_amount;
			$this->is_completed = true;
			$this->completed_at = now();
		}

		$this->save();

		return $this;
	}

	/**
	 * Withdraw amount from savings
	 */
	public function withdrawAmount($amount)
	{
		if ($amount > $this->current_amount) {
			throw new \Exception("Amount exceeds current savings");
		}

		$this->current_amount -= $amount;
		$this->is_completed = false;
		$this->completed_at = null;
		$this->save();

		return $this;
	}

	/**
	 * Scope for active goals (not completed)
	 */
	public function scopeActive($query)
	{
		return $query->where("is_completed", false);
	}

	/**
	 * Scope for completed goals
	 */
	public function scopeCompleted($query)
	{
		return $query->where("is_completed", true);
	}

	/**
	 * Scope for goals by priority
	 */
	public function scopeByPriority($query, $priority)
	{
		return $query->where("priority", $priority);
	}

	/**
	 * Scope for upcoming goals (within 30 days)
	 */
	public function scopeUpcoming($query)
	{
		return $query
			->where("target_date", "<=", now()->addDays(30))
			->where("is_completed", false);
	}

	/**
	 * Scope for overdue goals
	 */
	public function scopeOverdue($query)
	{
		return $query
			->where("target_date", "<", now())
			->where("is_completed", false);
	}

	/**
	 * Calculate days late
	 */
	public function getDaysLateAttribute()
	{
		if ($this->is_completed || $this->target_date >= now()) {
			return 0;
		}

		return now()->diffInDays($this->target_date);
	}

	/**
	 * Get status based on progress and timeline
	 */
	public function getStatusAttribute()
	{
		if ($this->is_completed) {
			return "completed";
		}

		if ($this->days_late > 0) {
			return "overdue";
		}

		if ($this->progress_percentage >= 80) {
			return "almost_there";
		}

		if ($this->is_on_track) {
			return "on_track";
		}

		return "behind_schedule";
	}

	/**
	 * Get status label
	 */
	public function getStatusLabelAttribute()
	{
		$labels = [
			"completed" => "Tercapai",
			"overdue" => "Terlambat",
			"almost_there" => "Hampir Tercapai",
			"on_track" => "Tepat Waktu",
			"behind_schedule" => "Tertinggal",
		];

		return $labels[$this->status] ?? "Tidak Diketahui";
	}
}
