<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\CoreController;
use Modules\Core\Http\Controllers\ServerMonitorController;

Route::middleware(["auth"])
	->prefix("admin")
	->group(function () {
		Route::prefix("cores")
			->name("cores.")
			->group(function () {
				Route::prefix("modules")
					->name("modules.")
					->group(function () {
						Route::get("", [CoreController::class, "index"])->name("index");
						Route::post("install-package", [
							CoreController::class,
							"installPackage",
						])->name("install-package");
						Route::post("update-package", [
							CoreController::class,
							"updatePackage",
						])->name("update-package");
						Route::post("disable", [
							CoreController::class,
							"disableModule",
						])->name("disable");
						Route::post("enable", [
							CoreController::class,
							"enableModule",
						])->name("enable");
					});
				Route::prefix("systems")
					->name("systems.")
					->group(function () {
						Route::get("", [ServerMonitorController::class, "index"])->name(
							"index"
						);
					});
			});

		Route::get("test", [ServerMonitorController::class, "test"]);
	});
