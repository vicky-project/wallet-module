<?php
namespace Modules\Wallet\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Wallet\Casts\MoneyCast;
use Modules\Wallet\Enums\RecurringFreq;
use Modules\Wallet\Enums\TransactionType;

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
		"type" => TransactionType::class,
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

	public function getNextDueDate(): ?Carbon
	{
		if (!$this->is_active) {
			return null;
		}

		$today = now();
		$startDate = Carbon::parse($this->start_date);

		// Jika belum pernah diproses, mulai dari start_date
		$lastDate = $this->last_processed
			? Carbon::parse($this->last_processed)
			: $startDate->copy()->subDay();

		return match ($this->frequency) {
			RecurringFreq::DAILY => $lastDate->addDays($this->interval),
			RecurringFreq::WEEKLY => $lastDate->addWeeks($this->interval),
			RecurringFreq::MONTHLY => $lastDate->addMonths($this->interval),
			RecurringFreq::QUARTERLY => $lastDate->addMonths(3 * $this->interval),
			RecurringFreq::YEARLY => $lastDate->addYears($this->interval),
			default => null,
		};
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

	private function checkCustomSchedule(Carbon $date): bool
	{
		$schedule = $this->custom_schedule ?? [];
		return in_array($date->format("Y-m-d"), $schedule);
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

	public function getFrequencyLabel(): string
	{
		return $this->frequency->label();
	}

	public function getNextOccurrenceText(): string
	{
		$nextDate = $this->getNextDueDate();

		if (!$nextDate) {
			return "Tidak aktif";
		}

		if ($nextDate->isToday()) {
			return "Hari ini";
		}

		if ($nextDate->isTomorrow()) {
			return "Besok";
		}

		return $nextDate->format("d M Y");
	}
}
