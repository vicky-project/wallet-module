<?php
namespace Modules\Wallet\Enums;

enum PaymentMethod: string
{
	case CASH = "cash";
	case TRANSFER = "transfer";
	case EWALLET = "ewallet";
	case CREDIT_CARD = "credit_card";
	case OTHER = "other";
}
