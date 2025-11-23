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
		$page = $request->get("page", 1);

		$packagistModule = $this->packagistService->getModule($page);
		dd($packagistModule);

		return view("core::index");
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
	public function show($id)
	{
		return view("core::show");
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
