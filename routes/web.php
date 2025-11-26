<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\CoreController;

Route::middleware(["auth"])
	->prefix("admin")
	->group(function () {
		Route::prefix("cores")
			->name("cores.")
			->group(function () {
				Route::get("", [CoreController::class, "index"])->name("modules.index");
				Route::post("install-package", [
					CoreController::class,
					"installPackage",
				])->name("install-package");
				Route::post("update-package", [
					CoreController::class,
					"updatePackage",
				])->name("update-package");
				Route::post("disable", [CoreController::class, "disableModule"])->name(
					"disable"
				);
				Route::post("enable", [CoreController::class, "enableModule"])->name(
					"enable"
				);
			});
	});
