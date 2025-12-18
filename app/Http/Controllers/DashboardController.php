<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DashboardController extends Controller
{
	public function index()
	{
		return view("core::dashboard.index", [
			"title" => "Dashboard",
			"breadcrumbs" => [["name" => "Dashboard", "active" => true]],
		]);
	}
}
