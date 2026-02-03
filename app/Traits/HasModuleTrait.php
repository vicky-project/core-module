<?php

namespace Modules\Core\Traits;

use Nwidart\Modules\Facades\Module;

trait HasModuleTrait
{
	public static function bootHasModuleTrait()
	{
		static::booted(function ($model) {
			$model->mergeFillable(static::getModuleFillableAtributes());
			$model->mergeCasts(static::getModuleCasts());

			static::setupModuleExtensions($model);
		});
	}

	protected static function getModuleFillableAtributes()
	{
		$fillable = ["telegram_id", "telegram_username", "auth_date"];

		try {
			$modules = Module::allEnabled();

			foreach ($modules as $module) {
				$config = Module::config(
					$module->getLowerName() . ".table_fields.fillable",
					[]
				);

				$fillable = array_merge($fillable, $config);
			}
		} catch (\Exception $e) {
			throw new \Exception(
				"Failed to get Module fillabel attributes: " . $e->getMessage()
			);
		}

		return array_unique($fillable);
	}

	protected static function getModuleCasts()
	{
		$casts = ["auth_date" => "timestamp"];

		try {
			$modules = Module::allEnabled();

			foreach ($modules as $module) {
				$config = Module::config(
					$module->getLowerName() . "table_fields.casts",
					[]
				);
				$casts = array_merge($casts, $config);
			}
		} catch (\Exception $e) {
			throw new \Exception(
				"Failed to get Module casts attribute: " . $e->getMessage()
			);
		}

		return $casts;
	}

	protected static function setupModuleExtensions()
	{
		event(new \Modules\Core\Events\UserModelBooted());
	}
}
