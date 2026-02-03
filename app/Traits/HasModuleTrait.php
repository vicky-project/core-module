<?php

namespace Modules\Core\Traits;

use Nwidart\Modules\Facades\Module;

trait HasModuleTrait
{
	protected $mergeFillable = ["telegram_id", "telegram_username", "auth_date"];

	public static function boot()
	{
		static::boot(function ($model) {
			$model->mergeFillable($this->mergeFillable);
		});
	}
}
