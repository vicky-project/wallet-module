<?php

use Illuminate\Support\Facades\Route;
use Modules\Wallet\Http\Controllers\AccountController;
use Modules\Wallet\Http\Controllers\TransactionController;
use Modules\Wallet\Http\Controllers\CategoryController;
use Modules\Wallet\Http\Controllers\DashboardController;
use Modules\Wallet\Http\Controllers\BudgetController;
use Modules\Wallet\Http\Controllers\RecurringController;

Route::middleware(["auth"])
	->prefix("apps")
	->name("apps.")
	->group(function () {
		Route::get("preview", [DashboardController::class, "index"])->name(
			"financial"
		);
		Route::get("preview/refresh", [
			DashboardController::class,
			"refresh",
		])->name("refresh");

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
		Route::get("transactions/export", [
			TransactionController::class,
			"export",
		])->name("transactions.export");
		Route::post("transactions/bulk-delete", [
			TransactionController::class,
			"bulkDelete",
		])->name("transactions.bulk-delete");
		Route::post("transactions/{uuid}/duplicate", [
			TransactionController::class,
			"duplicate",
		])->name("transactions.duplicate");
		Route::post("transactions/check-budget", [
			TransactionController::class,
			"checkBudget",
		])->name("transactions.check-budget");
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
			"updateSpentAmounts",
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
			"duplicate",
		])->name("budgets.duplicate");
		Route::put("budgets/{budget}/reset-spent", [
			BudgetController::class,
			"resetSpent",
		])->name("budgets.reset-spent");
		Route::resource("budgets", BudgetController::class);

		// Recurring routes
		Route::resource("recurrings", RecurringController::class);
	});
