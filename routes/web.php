<?php

use Illuminate\Support\Facades\Route;
use Modules\Wallet\Http\Controllers\WalletController;
use Modules\Wallet\Http\Controllers\TransactionController;
use Modules\Wallet\Http\Controllers\CategoryController;

Route::middleware(["auth"])
	->prefix("apps")
	->name("apps.")
	->group(function () {
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
		Route::post("transactions/transfer", [
			TransactionController::class,
			"transfer",
		])->name("transactions.transfer");
		Route::resource("transactions", TransactionController::class);

		// Category Routes
		Route::resource("categories", CategoryController::class);
	});
