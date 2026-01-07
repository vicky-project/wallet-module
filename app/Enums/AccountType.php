<?php
namespace Modules\Wallet\Enums;

enum AccountType: string
{
	case CASH = "cash";
	case BANK = "bank";
	case E_WALLET = "ewallet";
	case CREDIT_CARD = "credit_card";
	case INVESTMENT = "investment";
	case SAVINGS = "savings";
	case OTHER = "other";

	public function label(): string
	{
		return match ($this) {
			self::CASH => "Tunai",
			self::BANK => "Bank",
			self::E_WALLET => "E-Wallet",
			self::CREDIT_CARD => "Kartu Kredit",
			self::INVESTMENT => "Investasi",
			self::SAVINGS => "Tabungan",
			self::OTHER => "Lainnya",
		};
	}
}
