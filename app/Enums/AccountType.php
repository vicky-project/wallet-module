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

	public function icon(): string
	{
		return match ($this) {
			self::CASH => "bi-cash-stack",
			self::BANK => "bi-bank",
			self::E_WALLET => "bi-phone",
			self::CREDIT_CARD => "bi-credit-card",
			self::INVESTMENT => "bi-graph-up",
			self::SAVINGS => "bi-piggy-bank",
			self::OTHER => "bi-coin",
		};
	}
}
