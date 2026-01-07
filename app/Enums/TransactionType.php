<?php
namespace Modules\Wallet\Enums;

enum TransactionType: string
{
	case INCOME = "income";
	case EXPENSE = "expense";
	case TRANSFER = "transfer";
}
