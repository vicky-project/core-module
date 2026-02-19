<?php

namespace Modules\Core\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends BaseController
{
	public function index(Request $request)
	{
		return view("core::dashboard.index", [
			"title" => "Dashboard",
			"breadcrumbs" => [["name" => "Dashboard", "active" => true]],
		]);
	}
}
