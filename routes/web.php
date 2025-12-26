<?php

use Illuminate\Support\Facades\Route;
use Modules\Wallet\Http\Controllers\AccountController;
use Modules\Wallet\Http\Controllers\WalletController;
use Modules\Wallet\Http\Controllers\TransactionController;
use Modules\Wallet\Http\Controllers\CategoryController;

Route::middleware(["auth"])
	->prefix("apps")
	->name("apps.")
	->group(function () {
		// Account Routes
		Route::post("accounts/default", [
			AccountController::class,
			"setDefault",
		])->name("accounts.default");
		Route::resource("accounts", AccountController::class);
		Route::get("accounts/{account}/summary", [
			AccountController::class,
			"summary",
		])->name("accounts.summary");

		// Wallet Routes
		Route::post("wallets/default", [
			WalletController::class,
			"setDefault",
		])->name("wallets.default");
		Route::resource("wallets", WalletController::class);
		Route::post("wallets/{wallet}/deposit", [
			WalletController::class,
			"deposit",
		])->name("wallets.deposit");
		Route::post("wallets/{wallet}/withdraw", [
			WalletController::class,
			"withdraw",
		])->name("wallets.withdraw");

		// Transaction Routes
		Route::resource("transactions", TransactionController::class);

		// Category Routes
		Route::resource("categories", CategoryController::class);
	});
