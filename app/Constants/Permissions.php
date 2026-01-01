<?php

namespace Modules\Wallet\Constants;

class Permissions
{
	// Wallet permissions
	const VIEW_ACCOUNTS = "financial.accounts.view";
	const CREATE_ACCOUNTS = "financial.accounts.create";
	const EDIT_ACCOUNTS = "financial.accounts.edit";
	const DELETE_ACCOUNTS = "financial.accounts.delete";

	// Transaction permissions
	const VIEW_TRANSACTIONS = "financial.transactions.view";
	const CREATE_TRANSACTIONS = "financial.transactions.create";
	const EDIT_TRANSACTIONS = "financial.transactions.edit";
	const DELETE_TRANSACTIONS = "financial.transactions.delete";

	// Category permissions
	const VIEW_CATEGORIES = "financial.categories.view";
	const CREATE_CATEGORIES = "financial.categories.create";
	const EDIT_CATEGORIES = "financial.categories.edit";
	const DELETE_CATEGORIES = "financial.categories.delete";

	public static function all(): array
	{
		return [
			// Wallet
			self::VIEW_ACCOUNTS => "View accounts",
			self::CREATE_ACCOUNTS => "Create accounts",
			self::EDIT_ACCOUNTS => "Edit accounts",
			self::DELETE_ACCOUNTS => "Delete accounts",

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
