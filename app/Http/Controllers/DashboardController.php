<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends BaseController
{
	public function index()
	{
		return view("core::dashboard.index", [
			"title" => "Dashboard",
			"breadcrumbs" => [["name" => "Dashboard", "active" => true]],
		]);
	}
}
