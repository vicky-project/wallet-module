<?php
namespace Modules\Wallet\Enums;

enum AccountType: string
{
	case GENERAL = "general";
	case SAVING = "saving";
	case CHECKING = "checking";
	case INVESTMENT = "investment";
}
