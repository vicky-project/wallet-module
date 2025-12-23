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
	const CREATE_WALLETS = "financial.wallets.wallet.create";
	const EDIT_WALLETS = "financial.wallets.wallet.edit";
	const DELETE_WALLETS = "financial.wallets.wallet.delete";

	public static function all(): array
	{
		return [
			// Account Wallet
			self::VIEW_ACCOUNTS => "View accounts wallet",
			self::CREATE_ACCOUNTS => "Create accounts wallet",
			self::EDIT_ACCOUNTS => "Edit accounts wallet",
			self::DELETE_ACCOUNTS => "Delete account wallet",
			self::MANAGE_ACCOUNTS => "Manage accounts wallet",

			// Wallet
			self::VIEW_WALLETS => "View wallets",
			self::CREATE_WALLETS => "Create wallets",
			self::EDIT_WALLETS => "Edit wallets",
			self::DELETE_WALLETS => "Delete wallets",
		];
	}
}
