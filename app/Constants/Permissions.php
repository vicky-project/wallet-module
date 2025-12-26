<?php

namespace Modules\Wallet\Constants;

class Permissions
{
	// Account permissions
	const VIEW_ACCOUNTS = "financial.accounts.view";
	const CREATE_ACCOUNTS = "financial.accounts.create";
	const EDIT_ACCOUNTS = "financial.accounts.edit";
	const DELETE_ACCOUNTS = "financial.accounts.delete";
	const MANAGE_ACCOUNTS = "financial.accounts.manage";

	// Wallet permissions
	const VIEW_WALLETS = "financial.wallets.view";
	const CREATE_WALLETS = "financial.wallets.create";
	const EDIT_WALLETS = "financial.wallets.edit";
	const DELETE_WALLETS = "financial.wallets.delete";

	// Transaction permissions
	const VIEW_TRANSACTIONS = "financial.transactions.view";
	const CREATE_TRANSACTIONS = "financial.transactions.create";
	const EDIT_TRANSACTIONS = "financial.transactions.edit";
	const DELETE_TRANSACTIONS = "financial.transactions.delete";

	// Category permissions
	const VIEW_CATEGORIES = "financial.wallets.view";
	const CREATE_CATEGORIES = "financial.wallets.create";
	const EDIT_CATEGORIES = "financial.wallets.edit";
	const DELETE_CATEGORIES = "financial.wallets.delete";

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

			// Transactions
			self::VIEW_TRANSACTIONS => "View transactions",
			self::CREATE_TRANSACTIONS => "Create transactions",
			self::EDIT_TRANSACTIONS => "Edit transactions",
			self::DELETE_TRANSACTIONS => "Delete transactions",

			// Category
			self::VIEW_CATEGORIES => "View category",
			self::CREATE_CATEGORIES => "Create category",
			self::EDIT_CATEGORIES => "Edit category",
			self::DELETE_CATEGORIES => "Delete category",
		];
	}
}
