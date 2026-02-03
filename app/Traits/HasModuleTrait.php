<?php

namespace Modules\Core\Traits;

use Nwidart\Modules\Facades\Module;

trait HasModuleTrait
{
	public static function boot()
	{
		static::boot(function ($model) {
			$model->mergeFillable(["telegram_id", "telegram_username", "auth_date"]);
		})->mergeCasts(["auth_date" => "timestamp"]);
	}
}
