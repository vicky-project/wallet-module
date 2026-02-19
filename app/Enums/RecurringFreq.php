<?php
namespace Modules\Wallet\Enums;

enum RecurringFreq: string
{
	case DAILY = "daily";
	case WEEKLY = "weekly";
	case MONTHLY = "monthly";
	case QUARTERLY = "quarterly";
	case YEARLY = "yearly";
	case CUSTOM = "custom";

	public function label(): string
	{
		return match ($this) {
			self::DAILY => "Harian",
			self::MONTHLY => "Bulanan",
			self::WEEKLY => "Mingguan",
			self::QUARTERLY => "Triwulan",
			self::YEARLY => "Tahunan",
			self::CUSTOM => "Kustom",
		};
	}

	public function icon(): string
	{
		return match ($this) {
			self::DAILY => "bi-calendar-day",
			self::MONTHLY => "bi-calendar-month",
			self::WEEKLY => "bi-calendar-week",
			self::QUARTERLY => "bi-calendar-range",
			self::YEARLY => "bi-calendar-event",
			self::CUSTOM => "bi-calendar",
		};
	}
}
