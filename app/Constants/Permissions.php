<?php

namespace Modules\Wallet\Constants;

class Permissions
{
	// Wallet permissions
	const VIEW_WALLETS = "financial.wallets.view";
	const CREATE_WALLETS = "financial.wallets.create";
	const EDIT_WALLETS = "financial.wallets.edit";
	const DELETE_WALLETS = "financial.wallets.delete";
	const DEPOSIT_WALLETS = "financial.wallets.deposit";
	const WITHDRAW_WALLETS = "financial.wallets.withdraw";
	const TRANSFER_WALLETS = "financial.wallets.transfer";

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
			// Wallet
			self::VIEW_WALLETS => "View wallets",
			self::CREATE_WALLETS => "Create wallets",
			self::EDIT_WALLETS => "Edit wallets",
			self::DELETE_WALLETS => "Delete wallets",
			self::DEPOSIT_WALLETS => "Deposit wallets",
			self::WITHDRAW_WALLETS => "Withdraw wallets",
			self::TRANSFER_WALLETS => "Transfer wallets",

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
