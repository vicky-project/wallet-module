<?php
namespace Modules\Wallet\Enums;

enum WalletType: string
{
	case LOW = "low";
	case MEDIUM = "medium";
	case HIGH = "high";
}
