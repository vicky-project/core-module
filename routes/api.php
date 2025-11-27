<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\ServerMonitorController;

Route::prefix("v1")->group(function () {
	Route::prefix("cores")
		->names("cores.")
		->group(function () {
			Route::get("metrics", [
				ServerMonitorController::class,
				"streamMetrics",
			])->name("metrics");
		});
});
