<?php
namespace Modules\Wallet\Enums;

enum PaymentMethod: string
{
	case CASH = "cash";
	case BANK = "bank";
	case EWALLET = "ewallet";
	case CREDIT_CARD = "credit_card";
}
