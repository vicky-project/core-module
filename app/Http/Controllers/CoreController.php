<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Core\Services\PackagistService;
use Modules\Core\Services\ModuleManagerService;

class CoreController extends Controller
{
	protected $packagistService;
	protected $moduleService;

	public function __construct(
		PackagistService $packagistService,
		ModuleManagerService $moduleService
	) {
		$this->packagistService = $packagistService;
		$this->moduleService = $moduleService;
	}

	/**
	 * Display a listing of the resource.
	 */
	public function index(Request $request)
	{
		$vendorName = config("core.vendor_name");
		$packagistModule = $this->packagistService->getVendorPackageWithVersionInfo(
			$vendorName
		);
		$installedModule = $this->moduleService->getInstalledModules();
		$availableModule = $this->moduleService->getAvailableModules();
		$allModules = $this->mergeModulesData(
			$packagistModule,
			$installedModule,
			$availableModule
		);

		dd($allModules);

		return view("core::modules.index", compact("modules"));
	}

	private function mergeModulesData(
		$packagistPackages,
		$installedModule,
		$availableModule
	) {
		$mergeData = [];

		foreach ($packagistPackages as $package) {
			$moduleName = $this->moduleService->extractModuleNameFromPackage(
				$package["name"]
			);

			$mergeData[$package["name"]] = [
				"source" => "packagist",
				"name" => $package["name"],
				"display_name" => $moduleName,
				"description" => $package["description"],
				"latest_version" => $package["latest_version"],
				"installed_version" => $package["installed_version"],
				"is_installed" => $package["is_installed"],
				"update_available" => $package["update_available"],
				"status" => $package["is_installed"]
					? $this->moduleService->getModuleStatus($moduleName)
					: "not_installed",
				"github_stars" => $package["github_stars"],
				"downloads" => $package["downloads"],
				"favers" => $package["favers"],
				"repository" => $package["repository"],
				"type" => $package["type"],
			];
		}

		foreach ($availableModule as $moduleName => $moduleInfo) {
			$composerName =
				$moduleInfo["composer_name"] ?? "vicky-project/{$moduleName}";

			if (!isset($mergeData[$composerName])) {
				$mergeData[$composerName] = [
					"source" => "local",
					"name" => $composerName,
					"display_name" => $moduleName,
					"description" => $moduleInfo["description"],
					"latest_version" => $moduleInfo["version"],
					"installed_version" => null,
					"is_installed" => false,
					"update_available" => false,
					"status" => "not_installed",
					"github_stars" => 0,
					"downloads" => ["monthly" => 0, "total" => 0],
					"favers" => 0,
					"repository" => "",
					"type" => "library",
				];
			}
		}

		return array_values($mergeData);
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
