<?php

use Illuminate\Support\Facades\Route;

use Modules\Wallet\Http\Controller\BudgetController;

Route::prefix("apps")
	->name("apps.")
	->group(function () {
		Route::post("budgets/bulk-update", [
			BudgetController::class,
			"bulkUpdate",
		])->name("budgets.bulk-update");
		Route::get("budgets/calculate-dates", [
			BudgetController::class,
			"calculateDates",
		])->name("budgets.calculate-dates");
		Route::get("budgets/suggested-amount/{category}", [
			BudgetController::class,
			"suggestedAmount",
		])->name("budgets.suggested-amount");
	});
