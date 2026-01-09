<?php

use Illuminate\Support\Facades\Route;

use Modules\Wallet\Http\Controller\AccountController;

Route::prefix("apps")
	->name("apps.")
	->group(function () {
		Route::post("recalculate", [
			AccountController::class,
			"recalculateBalance",
		])->name("accounts.recalculate");
	});
