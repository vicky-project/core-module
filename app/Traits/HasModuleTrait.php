<?php

namespace Modules\Core\Traits;

use Nwidart\Modules\Facades\Module;

trait HasModuleTrait
{
	public static function bootHasModuleTrait()
	{
		static::mergeFillable(static::getModuleFillableAtributes());
		static::mergeCasts(static::getModuleCasts());

		static::setupModuleExtensions();
	}

	protected static function getModuleFillableAtributes()
	{
		$fillable = ["telegram_id", "telegram_username", "auth_date"];

		$modules = Module::allEnabled();

		foreach ($modules as $module) {
			$config = Module::config(
				$module->getLowerName() . ".table_fields.fillable",
				[]
			);

			$fillable = array_merge($fillable, $config);
		}

		return array_unique($fillable);
	}

	protected static function getModuleCasts()
	{
		$casts = ["auth_date" => "timestamp"];

		$modules = Module::allEnabled();

		foreach ($modules as $module) {
			$config = Module::config(
				$module->getLowerName() . "table_fields.casts",
				[]
			);
			$casts = array_merge($casts, $config);
		}

		return $casts;
	}

	protected static function setupModuleExtensions()
	{
		static::booted(function ($model) {
			event(new \Modules\Core\Events\UserModelBooted());
		});
	}
}
