<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\CoreController;

Route::middleware(["auth"])
	->prefix("admin")
	->group(function () {
		Route::prefix("cores")
			->name("cores.")
			->group(function () {
				Route::get("", [CoreController::class, "index"])->name("index");
				Route::post("install-package/{module}", [
					CoreController::class,
					"installPackage",
				])->name("install-package");
				Route::post("update-package/{module}", [
					CoreController::class,
					"updatePackage",
				])->name("update-package");
				Route::post("disable/{module}", [
					CoreController::class,
					"disableModule",
				])->name("disable");
				Route::post("enable/{module}", [
					CoreController::class,
					"enableModule",
				])->name("enable");
			});
	});
