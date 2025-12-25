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

		Route::resource("wallets", WalletController::class);

		Route::resource("transactions", TransactionController::class);

		Route::resource("categories", CategoryController::class);
	});
