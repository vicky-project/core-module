<?php

namespace Modules\Core\Traits;

use Modules\Core\Events\ModuleTraitEvent;
use Nwidart\Modules\Facades\Module;

trait HasModuleTrait
{
	public static function bootHasModuleTrait()
	{
		static::booted(function ($model) {
			$modules = Module::allEnabled();

			foreach ($modules as $module) {
				$config = Module::config($module->getLowerName());

				if (isset($config["model_extensions"])) {
					foreach ($config["model_extensions"] as $extensionClass) {
						if (class_exists($extensionClass)) {
							$extensionClass::extend($model);
						}
					}
				}
			}
		});
	}
}
