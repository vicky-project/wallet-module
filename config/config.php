<?php

return [
	"name" => "Wallet",
	"default_currency" => "IDR",
	"back_to_server_url" => env("WALLET_SERVER_URL", config("app.url", null)),
	"cache_ttl" => env("WALLET_CACHE_TTL", 3600),

	/**
	 * Telegram bot configuration
	 */
	"telegram_bot" => [
		"token" => env("TELEGRAM_BOT_TOKEN"),
		"username" => env("TELEGRAM_BOT_USERNAME", "your_bot_username"),
		"webhook_url" => env("TELEGRAM_WEBHOOK_URL", "/api/telegram/webhook"),
		"webhook_secret" => env("TELEGRAM_WEBHOOK_SECRET"),
		"admin" => env("TELEGRAM_ADMINS", ""), // String of id with comma separated
		"table_fields" => [
			"telegram_id" => "integer",
			"telegram_verification_code" => "string",
			"telegram_code_expires_at" => "timestamp",
			"telegram_notifications" => "bool",
			"telegram_settings" => "array",
		],
	],

	/**
	 * Guess category name by description text using include text bellow inside description text. This useful when import e-staatement from any bank. You can add more for accurating guess.
	 */
	"guess_category_by_text" => [
		"admin" => ["Biaya", "debit", "kartu kredit"],
		"rumah" => ["PLN", "Telkom/Indihome", "PERUMAHAN"],
		"belanja" => ["ShopeePay", "Shopee", "GRAB", "Danatopup"],
		"transfer" => ["Transfer", "Pembayaran", "QR"],
		"pulsa" => ["IM3", "Telkomsel", "IM3Ooredoo"],
		"tarik_tunai" => ["Tarik tunai", "tunai", "ATM"],
	],

	/**
	 * List of class that parsing data from banking.
	 */
	"bank_parsers" => [\Modules\Wallet\Parsers\MandiriStatementParser::class],

	/**
	 * Metadata Application
	 */
	"metadata" => [
		"company_name" => config("app.name", "Financial Item"),
		"company_address" => config(
			"app.address",
			"Jl. antah berantah, Dagelan, Indonesia"
		),
	],
];
