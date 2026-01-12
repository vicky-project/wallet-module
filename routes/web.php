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
		Route::put("accounts/{account}/set-default", [
			AccountController::class,
			"setDefault",
		])->name("accounts.set-default");
		Route::post("accounts/recalculate-all", [
			AccountController::class,
			"recalculateAllBalance",
		])->name("accounts.recalculate-all");
		Route::post("accounts/{account}/recalculate", [
			AccountController::class,
			"recalculateBalance",
		])->name("accounts.recalculate");
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
		Route::post("categories/bulk-update", [
			CategoryController::class,
			"bulkUpdate",
		])->name("categories.bulk-update");
		Route::post("categories/import", [CategoryController::class, ""])->name(
			"categories.import"
		);
		Route::delete("categories/bulk-delete", [
			CategoryController::class,
			"",
		])->name("categories.bulk-delete");
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
		Route::post("budgets/{budget}/toggle-status", [
			BudgetController::class,
			"toggleStatus",
		])->name("budgets.toggle-status");
		Route::get("budgets/{budget}/duplicate", [
			BudgetController::class,
			"",
		])->name("budgets.duplicate");
		Route::resource("budgets", BudgetController::class);
	});
