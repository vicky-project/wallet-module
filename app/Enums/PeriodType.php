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
}
