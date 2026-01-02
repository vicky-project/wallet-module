<?php

use Illuminate\Support\Facades\Route;
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
		Route::put("categories/{category}/toggle-status", [
			CategoryController::class,
			"toggleStatus",
		])->name("categories.toggle-status");
		Route::resource("categories", CategoryController::class);
	});
