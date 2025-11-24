<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Core\Services\PackagistService;

class CoreController extends Controller
{
	protected $packagistService;

	public function __construct(PackagistService $packagistService)
	{
		$this->packagistService = $packagistService;
	}

	/**
	 * Display a listing of the resource.
	 */
	public function index(Request $request)
	{
		$packagistModule = $this->packagistService->searchLaravelModules();
		$installedModule = $this->packagistService->getInstalledModule();
		dd($installedModule, $installedModule);

		return view("core::modules.index", compact("modules"));
	}

	/**
	 * Show the form for creating a new resource.
	 */
	public function create()
	{
		return view("core::create");
	}

	/**
	 * Store a newly created resource in storage.
	 */
	public function store(Request $request)
	{
	}

	/**
	 * Show the specified resource.
	 */
	public function show($core)
	{
		$package = $this->packagistService->getModule($core);
		dd($package);
		return view("core::modules.show", compact("package"));
	}

	/**
	 * Show the form for editing the specified resource.
	 */
	public function edit($id)
	{
		return view("core::edit");
	}

	/**
	 * Update the specified resource in storage.
	 */
	public function update(Request $request, $id)
	{
	}

	/**
	 * Remove the specified resource from storage.
	 */
	public function destroy($id)
	{
	}
}
