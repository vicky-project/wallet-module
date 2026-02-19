<?php

use Illuminate\Support\Facades\Route;

use Modules\Wallet\Http\Controllers\BudgetController;
use Modules\Wallet\Http\Controllers\ReportController;
use Modules\Wallet\Http\Controllers\TelegramController;

Route::prefix("apps")
	->name("apps.")
	->group(function () {
		Route::prefix("telegram")
			->middleware(["auth::telegram"])
			->group(function () {
				Route::post("mini-app", [TelegramController::class, "handleData"]);
			});
		Route::prefix("reports")
			->name("reports.")
			->middleware(["auth", "web"])
			->group(function () {
				Route::get("dashboard-summary", [
					ReportController::class,
					"dashboardSummary",
				])->name("dashboard-summary");
				Route::get("monthly/{year}/{month}", [
					ReportController::class,
					"monthlyReport",
				]);
				Route::get("yearly/{year}", [ReportController::class, "yearlyReport"]);
				Route::post("custom", [ReportController::class, "customReport"])->name(
					"custom"
				);
				Route::post("export", [ReportController::class, "exportReport"])->name(
					"export"
				);
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
