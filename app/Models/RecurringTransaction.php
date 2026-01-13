<?php
namespace Modules\Wallet\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Wallet\Casts\MoneyCast;
use Modules\Wallet\Enums\RecurringFreq;

class RecurringTransaction extends Model
{
	use HasFactory, SoftDeletes;

	protected $fillable = [
		"user_id",
		"account_id",
		"category_id",
		"type",
		"amount",
		"description",
		"frequency",
		"interval",
		"start_date",
		"end_date",
		"day_of_month",
		"day_of_week",
		"custom_schedule",
		"is_active",
		"remaining_occurrences",
		"last_processed",
	];

	protected $casts = [
		"start_date" => "date",
		"end_date" => "date",
		"last_processed" => "date",
		"custom_schedule" => "array",
		"is_active" => "boolean",
		"amount" => MoneyCast::class,
		"frequency" => RecurringFreq::class,
	];

	// Relationships
	public function user()
	{
		return $this->belongsTo(config("auth.providers.users.model"));
	}

	public function account()
	{
		return $this->belongsTo(Account::class);
	}

	public function category()
	{
		return $this->belongsTo(Category::class);
	}

	public function transactions()
	{
		return $this->hasMany(Transaction::class, "recurring_template_id");
	}

	// Methods for generating transactions
	public function shouldProcessToday()
	{
		if (!$this->is_active) {
			return false;
		}

		$today = now();

		// Check if today is within date range
		if ($today->lt($this->start_date)) {
			return false;
		}
		if ($this->end_date && $today->gt($this->end_date)) {
			return false;
		}

		// Check remaining occurrences
		if (
			$this->remaining_occurrences !== null &&
			$this->remaining_occurrences <= 0
		) {
			return false;
		}

		// Check last processed
		if ($this->last_processed && $this->last_processed->isToday()) {
			return false;
		}

		// Check schedule based on frequency
		return $this->checkSchedule($today);
	}

	private function checkSchedule($date)
	{
		switch ($this->frequency) {
			case RecurringFreq::DAILY:
				return $this->interval == 1 ||
					$date->diffInDays($this->start_date) % $this->interval == 0;

			case RecurringFreq::WEEKLY:
				if ($this->day_of_week !== null) {
					return $date->dayOfWeek == $this->day_of_week &&
						($this->interval == 1 ||
							floor($date->diffInWeeks($this->start_date)) % $this->interval ==
								0);
				}
				return $date->dayOfWeek == $this->start_date->dayOfWeek &&
					($this->interval == 1 ||
						floor($date->diffInWeeks($this->start_date)) % $this->interval ==
							0);

			case RecurringFreq::MONTHLY:
				if ($this->day_of_month !== null) {
					return $date->day == $this->day_of_month &&
						($this->interval == 1 ||
							$date->diffInMonths($this->start_date) % $this->interval == 0);
				}
				return $date->day == $this->start_date->day &&
					($this->interval == 1 ||
						$date->diffInMonths($this->start_date) % $this->interval == 0);

			case RecurringFreq::YEARLY:
				return $date->format("m-d") == $this->start_date->format("m-d") &&
					($this->interval == 1 ||
						$date->diffInYears($this->start_date) % $this->interval == 0);

			case RecurringFreq::CUSTOM:
				// Implement custom schedule logic
				return $this->checkCustomSchedule($date);
		}

		return false;
	}

	public function process()
	{
		$transaction = Transaction::create([
			"user_id" => $this->user_id,
			"account_id" => $this->account_id,
			"category_id" => $this->category_id,
			"type" => $this->type,
			"amount" => $this->amount,
			"description" => $this->description,
			"transaction_date" => now(),
			"is_recurring" => true,
			"recurring_template_id" => $this->id,
		]);

		$this->last_processed = now();
		if ($this->remaining_occurrences !== null) {
			$this->remaining_occurrences--;
		}
		$this->save();

		return $transaction;
	}
}
