<?php

use Illuminate\Support\Facades\Route;

use Modules\Wallet\Http\Controllers\BudgetController;
use Modules\Wallet\Http\Controllers\ReportController;

Route::prefix("apps")
	->name("apps.")
	->group(function () {
		Route::prefix("reports")
			->middleware(["auth", "web"])
			->group(function () {
				Route::get("dashboard-summary", [
					ReportController::class,
					"dashboardSummary",
				]);
				Route::get("monthly/{year}/{month}", [
					ReportController::class,
					"monthlyReport",
				]);
				Route::get("yearly/{year}", [ReportController::class, "yearlyReport"]);
				Route::get("custom", [ReportController::class, "customReport"]);
				Route::post("export", [ReportController::class, "exportReport"]);
			});

		Route::post("budgets/bulk-update", [
			BudgetController::class,
			"bulkUpdate",
		])->name("budgets.bulk-update");
		Route::post("budgets/calculate-dates", [
			BudgetController::class,
			"calculateDates",
		])->name("budgets.calculate-dates");
		Route::get("budgets/suggested-amount/{category}", [
			BudgetController::class,
			"suggestedAmount",
		])->name("budgets.suggested-amount");
	});
