<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Modules\Core\Services\PackagistService;
use Modules\Core\Services\ModuleManagerService;
use Modules\Core\Constants\Permissions;

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

		$this->middleware(["permission:" . Permissions::VIEW_MODULES])->only([
			"index",
		]);
		$this->middleware(["permission:" . Permissions::MANAGE_MODULES])->only([
			"installPackage",
			"updatePackage",
			"disableModule",
			"enableModule",
		]);
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

		return view("core::modules.index", compact("allModules"));
	}

	private function mergeModulesData(
		$packagistPackages,
		$installedModule,
		$availableModule
	) {
		$mergedData = [];

		foreach ($packagistPackages as $package) {
			$moduleName = $this->packagistService->extractModuleNameFromPackage(
				$package["name"]
			);

			$status = "not_installed";
			if ($package["is_installed"]) {
				if ($package["is_local_module"]) {
					$status = $package["module_status"];
				} else {
					$status = "installed";
				}
			}

			if ($package["update_available"]) {
				logger()->info("Update available for {$package["name"]}:", [
					"current" => $package["installed_version"],
					"available" => $package["latest_version"],
				]);
			}

			$mergedData[$package["name"]] = [
				"source" => "packagist",
				"name" => $package["name"],
				"display_name" => $moduleName,
				"description" => $package["description"],
				"latest_version" => $package["latest_version"],
				"installed_version" => $package["installed_version"],
				"is_installed" => $package["is_installed"],
				"update_available" => $package["update_available"],
				"status" => $status,
				"is_local_module" => $package["is_local_module"],
				"github_stars" => $package["github_stars"],
				"downloads" => $package["downloads"],
				"favers" => $package["favers"],
				"repository" => $package["repository"],
				"type" => $package["type"],
			];
		}

		foreach ($installedModule as $moduleName => $moduleInfo) {
			$composerName =
				$moduleInfo["composer_name"] ?? "vicky-project/{$moduleName}";

			if (!isset($mergedData[$composerName])) {
				$mergedData[$composerName] = [
					"source" => "local",
					"name" => $composerName,
					"display_name" => $moduleName,
					"description" => $moduleInfo["original_description"],
					"latest_version" => $moduleInfo["version"],
					"installed_version" => $moduleInfo["version"],
					"is_installed" => true,
					"update_available" => false,
					"status" => $moduleInfo["status"],
					"github_stars" => 0,
					"downloads" => ["monthly" => 0, "total" => 0],
					"favers" => 0,
					"repository" => "",
					"type" => "library",
				];
			}
		}

		foreach ($availableModule as $moduleName => $moduleInfo) {
			$composerName =
				$moduleInfo["composer_name"] ?? "vicky-project/{$moduleName}";

			if (!isset($mergedData[$composerName])) {
				$mergedData[$composerName] = [
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

		return array_values($mergedData);
	}

	/**
	 * Show the form for creating a new resource.
	 */
	public function installPackage(Request $request)
	{
		$request->validate(["module" => "required|string"]);

		$module = $request->input("module", null);
		try {
			$result = $this->moduleService->installModule($module);
			Cache::forget(config("core.cache_key_prefix") . "_*");

			if ($result["success"] === true) {
				$moduleName = $result["module_name"];

				if ($this->moduleService->isLocalModule($moduleName)) {
					$this->moduleService->enableModule($moduleName);
				}

				return back()->with(
					"success",
					"Package {$moduleName} installed successfuly"
				);
			}
		} catch (\Exception $e) {
			return back()->withErrors(
				"Error installing package: " . $e->getMessage()
			);
		}
	}

	/**
	 * Store a newly created resource in storage.
	 */
	public function updatePackage(Request $request)
	{
		$request->validate(["module" => "required|string"]);

		$module = $request->input("module", null);
		try {
			$output = [];
			$returnCode = 0;

			$command = "composer require {$module}:* --update-with-dependencies --no-dev -n";

			exec($command, $output, $returnCode);

			if ($returnCode === 0) {
				Cache::forget(config("core.cache_key_prefix") . "_*");
				$moduleName = $this->moduleService->extractModuleNameFromPackage(
					$module
				);

				if ($this->moduleService->isLocalModule($module)) {
					Artisan::call("module:migrate", [
						"module" => $module,
						"--force" => true,
					]);
				}

				return back()->with("success", "Package {$module} updated successfuly");
			} else {
				$errorMessage = implode("\n", array_slice($output, -5));

				return back()->withErrors("Failed to update package: " . $errorMessage);
			}
		} catch (\Exception $e) {
			return back()->withErrors("Error updating package: " . $e->getMessage());
		}
	}

	/**
	 * Show the form for editing the specified resource.
	 */
	public function disableModule(Request $request)
	{
		$request->validate(["module" => "required|string"]);

		$module = $request->input("module", null);
		$result = $this->moduleService->disableModule($module);

		if ($result["success"]) {
			return back()->with("success", $result["message"]);
		}

		return back()->withErrors($result["message"]);
	}

	/**
	 * Update the specified resource in storage.
	 */
	public function enableModule(Request $request)
	{
		$request->validate(["module" => "required|string"]);

		$module = $request->input("module", null);
		$result = $this->moduleService->enableModule($module);

		if ($result["success"]) {
			return back()->with("success", $result["message"]);
		}

		return back()->withErrors($result["message"]);
	}
}
