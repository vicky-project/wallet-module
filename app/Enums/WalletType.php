<?php
namespace Modules\Wallet\Enums;

enum WalletType: string
{
	case CASH = "cash";
	case BANK = "bank";
	case DIGITAL = "digital";
	case CREDIT = "credit";
}
