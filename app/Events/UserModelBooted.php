<?php
namespace Modules\Core\Events;

use Illuminate\Database\Eloquent\Model;

class UserModelBooted
{
	protected Model $model;

	public function __construct(Model $model)
	{
		$this->model = $model;
	}
}
