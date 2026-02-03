<?php
namespace Modules\Core\Extensions;

use Illuminate\Database\Eloquent\Model;

class UserModelExtension
{
	public static function extend(Model $model)
	{
		$model->mergeFillable(["telegram_id", "telegram_username", "auth_date"]);
	}
}
