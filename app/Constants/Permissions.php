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

	public static function all(): array
	{
		return [
			// Wallet
			self::VIEW_ACCOUNTS => "View wallets",
			self::CREATE_ACCOUNTS => "Create wallets",
			self::EDIT_ACCOUNTS => "Edit wallets",
			self::DELETE_ACCOUNTS => "Delete wallets",
			self::MANAGE_ACCOUNTS => "Manage wallets (Re-calculate balance)",
		];
	}
}
