<?php

namespace Modules\Core\Services;

use Nwidart\Modules\Facades\Module;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ModuleManagerService
{
	/**
	 * Get all installed modules with detailed information
	 */
	public function getInstalledModules()
	{
		$installedModules = [];

		// Get all modules from nwidart/laravel-modules [citation:9]
		$allModules = Module::all();

		foreach ($allModules as $module) {
			$moduleInfo = [
				"name" => $module->getName(),
				"alias" => $module->getAlias(),
				"description" => $module->getDescription(),
				"version" => $module->get("version", "1.0.0"),
				"path" => $module->getPath(),
				"status" => $module->isEnabled() ? "enabled" : "disabled",
				"type" => "installed",
			];

			// Get additional info from composer.json
			$composerPath = $module->getPath() . "/composer.json";
			if (File::exists($composerPath)) {
				$composerContent = json_decode(File::get($composerPath), true);
				$moduleInfo["composer_name"] = $composerContent["name"] ?? null;
				$moduleInfo["original_description"] =
					$composerContent["description"] ?? $module->getDescription();
				$moduleInfo["keywords"] = $composerContent["keywords"] ?? [];
			}

			$installedModules[$module->getName()] = $moduleInfo;
		}

		return $installedModules;
	}

	/**
	 * Get available local modules (not installed via composer but present in Modules directory)
	 */
	public function getAvailableModules()
	{
		$availableModules = [];
		$modulesPath = base_path("Modules");

		if (!File::exists($modulesPath)) {
			return $availableModules;
		}

		$moduleDirectories = File::directories($modulesPath);

		foreach ($moduleDirectories as $modulePath) {
			$moduleName = basename($modulePath);

			// Skip if already installed in nwidart/laravel-modules
			if (Module::has($moduleName)) {
				continue;
			}

			$moduleInfo = [
				"name" => $moduleName,
				"path" => $modulePath,
				"status" => "not_installed",
				"type" => "available",
			];

			// Check for composer.json
			$composerPath = $modulePath . "/composer.json";
			if (File::exists($composerPath)) {
				$composerContent = json_decode(File::get($composerPath), true);
				$moduleInfo["description"] =
					$composerContent["description"] ?? "No description available";
				$moduleInfo["version"] = $composerContent["version"] ?? "1.0.0";
				$moduleInfo["composer_name"] = $composerContent["name"] ?? null;
			} else {
				$moduleInfo["description"] = "No composer.json found";
				$moduleInfo["version"] = "1.0.0";
			}

			$availableModules[$moduleName] = $moduleInfo;
		}

		return $availableModules;
	}

	/**
	 * Install a module (run migrations and enable)
	 */
	public function installModule($moduleName)
	{
		try {
			if (!Module::has($moduleName)) {
				return ["success" => false, "message" => "Module not found!"];
			}

			$module = Module::find($moduleName);

			// Run module migrations
			Artisan::call("module:migrate", ["module" => $moduleName]);

			// Enable the module
			$module->enable();

			return [
				"success" => true,
				"message" => "Module {$moduleName} installed and enabled successfully!",
			];
		} catch (\Exception $e) {
			Log::error(
				"Module installation error for {$moduleName}: " . $e->getMessage()
			);
			return [
				"success" => false,
				"message" => "Failed to install module: " . $e->getMessage(),
			];
		}
	}

	/**
	 * Enable a module [citation:9]
	 */
	public function enableModule($moduleName)
	{
		try {
			if (!Module::has($moduleName)) {
				return ["success" => false, "message" => "Module not found!"];
			}

			Module::enable($moduleName);

			return [
				"success" => true,
				"message" => "Module {$moduleName} enabled successfully!",
			];
		} catch (\Exception $e) {
			Log::error("Module enable error for {$moduleName}: " . $e->getMessage());
			return [
				"success" => false,
				"message" => "Failed to enable module: " . $e->getMessage(),
			];
		}
	}

	/**
	 * Disable a module [citation:9]
	 */
	public function disableModule($moduleName)
	{
		try {
			if (!Module::has($moduleName)) {
				return ["success" => false, "message" => "Module not found!"];
			}

			Module::disable($moduleName);

			return [
				"success" => true,
				"message" => "Module {$moduleName} disabled successfully!",
			];
		} catch (\Exception $e) {
			Log::error("Module disable error for {$moduleName}: " . $e->getMessage());
			return [
				"success" => false,
				"message" => "Failed to disable module: " . $e->getMessage(),
			];
		}
	}

	/**
	 * Get module status
	 */
	public function getModuleStatus($moduleName)
	{
		if (!Module::has($moduleName)) {
			return "not_installed";
		}

		$module = Module::find($moduleName);
		return $module->isEnabled() ? "enabled" : "disabled";
	}

	/**
	 * Extract module name from package name
	 */
	public function extractModuleNameFromPackage($packageName)
	{
		$parts = explode("/", $packageName);
		$name = end($parts);

		// Convert kebab-case to StudlyCase (laravel-module -> LaravelModule)
		return str_replace(" ", "", ucwords(str_replace("-", " ", $name)));
	}

	/**
	 * Check if a package corresponds to a local module
	 */
	public function isLocalModule($packageName)
	{
		$moduleName = $this->extractModuleNameFromPackage($packageName);
		return Module::has($moduleName);
	}
}
