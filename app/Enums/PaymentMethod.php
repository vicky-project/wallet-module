<?php
namespace Modules\Wallet\Enums;

enum PaymentMethod: string
{
	case CASH = "cash";
	case TRANSFER = "transfer";
	case EWALLET = "ewallet";
	case CREDIT_CARD = "credit_card";
	case OTHER = "other";

	public function label(): string
	{
		return match ($this) {
			self::CASH => "Cash",
			self::TRANSFER => "Transfer",
			self::EWALLET => "E-Wallet",
			self::CREDIT_CARD => "Credit Card",
			self::OTHER => "Other",
		};
	}
}
