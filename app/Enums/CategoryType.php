<?php
namespace Modules\Wallet\Enums;

enum CategoryType: string
{
	case INCOME = "income";
	case EXPENSE = "expense";
	case TRANSFER = "transfer";
}
