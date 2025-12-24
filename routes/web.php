<?php

use Illuminate\Support\Facades\Route;
use Modules\Wallet\Http\Controllers\AccountController;
use Modules\Wallet\Http\Controllers\WalletController;
use Modules\Wallet\Http\Controllers\TransactionController;

Route::middleware(["auth"])
	->prefix("apps")
	->name("apps.")
	->group(function () {
		// Account Routes
		Route::resource("accounts", AccountController::class)->names("wallet");

		// Wallet Routes
		Route::prefix("accounts/{account}")
			->name("wallet.")
			->group(function () {
				Route::resource("wallets", WalletController::class)->except(["index"]);
				Route::post("/wallets/{wallet}/refresh", [
					WalletController::class,
					"refreshBalance",
				])->name("wallets.refresh-balance");
			});

		// Transaction Routes
		Route::prefix("accounts/{account}/wallets/{wallet}/transactions")->group(
			function () {
				Route::get("/create", [TransactionController::class, "create"])->name(
					"wallet.transactions.create"
				);
				Route::post("/deposit", [
					TransactionController::class,
					"deposit",
				])->name("wallet.transactions.deposit");
				Route::post("withdraw", [
					TransactionController::class,
					"withdraw",
				])->name("wallet.transactions.withdraw");
			}
		);
	});
