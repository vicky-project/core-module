<?php
namespace Modules\Core\Http\Controllers;

use Illuminate\Routing\Controller;

class BaseController extends Controller
{
	protected function isPermissionMiddlewareExists(): bool
	{
		return class_exists(
			\Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class
		);
	}
}
