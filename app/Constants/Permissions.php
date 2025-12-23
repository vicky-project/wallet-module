<?php

namespace Modules\Wallet\Constants;

class Permissions
{
	// Wallet permissions
	const VIEW_ACCOUNTS = "financial.wallets.view";
	const CREATE_ACCOUNTS = "financial.wallets.create";
	const EDIT_ACCOUNTS = "financial.wallets.edit";
	const DELETE_ACCOUNTS = "financial.wallets.delete";
	const MANAGE_ACCOUNTS = "financial.wallets.manage";

	const VIEW_WALLETS = "financial.wallets.wallet.view";

	public static function all(): array
	{
		return [
			// Wallet
			self::VIEW_ACCOUNTS => "View accounts",
			self::CREATE_ACCOUNTS => "Create accounts",
			self::EDIT_ACCOUNTS => "Edit accounts",
			self::DELETE_ACCOUNTS => "Delete account",
			self::MANAGE_ACCOUNTS => "Manage accounts",
		];
	}
}
