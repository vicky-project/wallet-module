<?php
namespace Modules\Wallet\Enums;

enum PeriodType: string
{
	case MONTHLY = "monthly";
	case WEEKLY = "weekly";
	case BIWEEKLY = "biweekly";
	case QUARTERLY = "quarterly";
	case YEARLY = "yearly";
	case CUSTOM = "custom";

	public function label(): string
	{
		return match ($this) {
			self::MONTHLY => "Bulanan",
			self::WEEKLY => "Mingguan",
			self::BIWEEKLY => "Dua Mingguan",
			self::QUARTERLY => "Triwulan",
			self::YEARLY => "Tahunan",
			self::CUSTOM => "Kustom",
		};
	}

	public function icon(): string
	{
		return match ($this) {
			self::MONTHLY => "bi-calendar-month",
			self::WEEKLY => "bi-calendar-week",
			self::BIWEEKLY => "bi-calendar2-week",
			self::QUARTERLY => "bi-calendar3",
			self::YEARLY => "bi-calendar-range",
			self::CUSTOM => "bi-calendar-event",
		};
	}
}
