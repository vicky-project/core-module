<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\ServerMonitorController;
use Modules\Core\Http\Controllers\ThemeController;

Route::prefix("v1")
	->name("v1.")
	->group(function () {
		Route::prefix("cores")
			->name("cores.")
			->group(function () {
				Route::prefix("theme")
					->name("theme.")
					->group(function () {
						Route::post("update", [ThemeController::class, "update"])->name(
							"update"
						);
					});

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
