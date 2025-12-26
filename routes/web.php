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
		Route::resource("accounts", AccountController::class);
		Route::get("accounts/{account}/summary", [
			AccountController::class,
			"summary",
		])->name("accounts.summary");

		// Wallet Routes
		Route::resource("wallets", WalletController::class);

		// Transaction Routes
		Route::resource("transactions", TransactionController::class);
		Route::post("transactions/{transaction}")->name("transactions.deposit");

		// Category Routes
		Route::resource("categories", CategoryController::class);
	});
