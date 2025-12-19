<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\ServerMonitorController;

Route::prefix("v1")
	->name("v1.")
	->group(function () {
		Route::prefix("cores")
			->name("cores.")
			->group(function () {
				Route::get("metrics", [
					ServerMonitorController::class,
					"streamMetrics",
				])->name("metrics");
				Route::get("health", [
					ServerMonitorController::class,
					"streamHealth",
				])->name("health");
			});
	});
