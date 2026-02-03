<?php
namespace Modules\Core\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

class UserModelBooted
{
	use Dispatchable;

	protected Model $model;

	public function __construct(Model $model)
	{
		$this->model = $model;
	}
}
