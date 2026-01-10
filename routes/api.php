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
	});
