<?php

use Illuminate\Support\Facades\Route;
use Modules\Wallet\Http\Controllers\AccountController;
use Modules\Wallet\Http\Controllers\TransactionController;
use Modules\Wallet\Http\Controllers\CategoryController;
use Modules\Wallet\Http\Controllers\DashboardController;
use Modules\Wallet\Http\Controllers\BudgetController;

Route::middleware(["auth"])
	->prefix("apps")
	->name("apps.")
	->group(function () {
		Route::get("preview", [DashboardController::class, "index"])->name(
			"financial"
		);

		// Account Routes
		Route::put("accounts/{account}/toggle-default", [
			AccountController::class,
			"toggleDefault",
		])->name("accounts.set-default");
		Route::resource("accounts", AccountController::class);

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

		// Budget Routes
		Route::get("budgets/update-spent", [
			BudgetController::class,
			"updateSpent",
		])->name("budgets.update-spent");
		Route::post("budgets/create-from-suggestions", [
			BudgetController::class,
			"createFromSuggestions",
		])->name("budgets.create-from-suggestions");
		Route::get("budgets/toggle-active", [
			BudgetController::class,
			"toggleActive",
		])->name("budgets.toggle-active");
		Route::resource("budgets", BudgetController::class);
	});
