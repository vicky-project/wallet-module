<?php
namespace Modules\Wallet\Enums;

enum CategoryType: string
{
	case INCOME = "deposit";
	case EXPENSE = "withdraw";
	case TRANSFER = "transfer";
}
