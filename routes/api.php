<?php

use Illuminate\Support\Facades\Route;

use Modules\Wallet\Http\Controller\BudgetController;

Route::prefix("apps")
	->name("app.")
	->group(function () {
		Route::post("budgets/bulk-update", [
			BudgetController::class,
			"bulkUpdate",
		])->name("budgets.bulk-update");
	});
