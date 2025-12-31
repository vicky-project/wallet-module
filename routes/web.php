<?php

use Illuminate\Support\Facades\Route;
use Modules\Wallet\Http\Controllers\WalletController;
use Modules\Wallet\Http\Controllers\TransactionController;
use Modules\Wallet\Http\Controllers\CategoryController;
use Modules\Wallet\Http\Controllers\DashboardController;

Route::middleware(["auth"])
	->prefix("apps")
	->name("apps.")
	->group(function () {
		Route::get("preview", [DashboardController::class, "index"])->name(
			"financial"
		);

		// Wallet Routes
		Route::post("wallets/default", [
			WalletController::class,
			"setDefault",
		])->name("wallets.default");
		Route::post("wallets/{wallet}/deposit", [
			WalletController::class,
			"deposit",
		])->name("wallets.deposit");
		Route::post("wallets/{wallet}/withdraw", [
			WalletController::class,
			"withdraw",
		])->name("wallets.withdraw");
		Route::resource("wallets", WalletController::class);

		// Transaction Routes
		Route::get("transactions/by-date", [
			TransactionController::class,
			"byDate",
		])->name("transactions.dates");
		Route::get("transactions/trash", [
			TransactionController::class,
			"trashed",
		])->name("transactions.trash");
		Route::post("transactions/transfer", [
			TransactionController::class,
			"transfer",
		])->name("transactions.transfer");
		Route::resource("transactions", TransactionController::class);

		// Category Routes
		Route::resource("categories", CategoryController::class);
	});
