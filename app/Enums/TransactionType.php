<?php
namespace Modules\Wallet\Enums;

enum TransactionType: string
{
	case INCOME = "income";
	case EXPENSE = "expense";
	case TRANSFER = "transfer";

	public function label(): string
	{
		return match ($this) {
			self::INCOME => "Pemasukan",
			self::EXPENSE => "Pengeluaran",
			self::TRANSFER => "Transfer",
		};
	}

	public function icon(): string
	{
		return match ($this) {
			self::INCOME => "bi-arrow-down-left",
			self::EXPENSE => "bi-arrow-up-right",
			self::TRANSFER => "bi-arrow-left-right",
		};
	}

	public function color(): string
	{
		return match ($this) {
			self::INCOME => "success",
			self::EXPENSE => "danger",
			self::TRANSFER => "primary",
		};
	}
}
