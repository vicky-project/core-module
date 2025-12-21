<?php
namespace Modules\Core\Http\Controllers;

use Illuminate\Routing\Controller;
use Nwidart\Modules\Facades\Module;

class BaseController extends Controller
{
	protected function isPermissionMiddlewareExists(): bool
	{
		return Module::has("UserManagement") && Module::isEnabled("UserManagement");
	}
}
