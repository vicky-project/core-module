<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Event;
use Modules\Core\Events\ModuleInstallationFailed;
use Modules\Core\Events\ModuleInstalled;
use Symfony\Component\Process\Process;
use Nwidart\Modules\Facades\Module;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ModuleManagerService
{
	protected $backupService;
	protected $composerService;

	public function __construct(
		BackupService $backupService,
		ComposerService $composerService
	) {
		$this->backupService = $backupService;
		$this->composerService = $composerService;
	}

	/**
	 * Install a module (run migrations and enable)
	 */
	public function installModule($packageName, $version = null)
	{
		try {
			// 1. Pre-installation check
			$this->preInstallationCheck($packageName, $version);

			// 2. Create backup
			$backupPath = $this->backupService->createBackup();

			// 3. Install via composer
			$this->composerService->requirePackage($packageName, $version);

			// 4. Extract module name
			$moduleName = $this->extractModuleName($packageName);

			// 5. Trigger event for async post-installation
			Event::dispatch(new ModuleInstalled($packageName, $version, $moduleName));

			return [
				"success" => true,
				"message" =>
					"Module installation started. Post-installation will run in background.",
				"module_name" => $moduleName,
				"backup_path" => $backupPath,
			];
		} catch (\Exception $e) {
			// Restore backup if installation fails
			if (isset($backupPath)) {
				$this->backupService->restoreBackup($backupPath);
			}

			Event::dispatch(
				new ModuleInstallationFailed(
					$packageName,
					$version,
					$e->getMessage(),
					$backupPath ?? null
				)
			);

			throw $e;
		}
	}

	protected function preInstallationCheck($packageName, $version)
	{
		// Check system requirements
		$this->checkSystemRequirements();

		// Check package exists and is accessible
		$this->composerService->validatePackage($packageName, $version);

		// Check dependencies and conflicts
		$this->checkDependencies($packageName);
		//$this->checkConflicts($packageName);

		// Check disk space
		$this->checkDiskSpace();
	}

	/**
	 * Extract module name from package name
	 */
	protected function extractModuleName($packageName)
	{
		// Extract module name from package name (vendor/package-name)
		$parts = explode("/", $packageName);
		$moduleName = end($parts);

		// Convert kebab-case to StudlyCase for module name
		return str_replace(" ", "", ucwords(str_replace("-", " ", $moduleName)));
	}

	protected function checkSystemRequirements()
	{
		$requirements = [
			"php" => [
				"minimum" => "8.0.0",
				"current" => PHP_VERSION,
				"check" => version_compare(PHP_VERSION, "8.0.0", ">="),
			],
			"memory_limit" => [
				"minimum" => "128M",
				"current" => ini_get("memory_limit"),
				"check" =>
					$this->memoryToBytes(ini_get("memory_limit")) >=
					$this->memoryToBytes("128M"),
			],
			"max_execution_time" => [
				"minimum" => 30,
				"current" => ini_get("max_execution_time"),
				"check" =>
					(int) ini_get("max_execution_time") >= 30 ||
					ini_get("max_execution_time") == 0,
			],
			"max_input_time" => [
				"minimum" => 60,
				"current" => ini_get("max_input_time"),
				"check" =>
					(int) ini_get("max_input_time") >= 60 ||
					ini_get("max_input_time") == -1,
			],
		];

		$errors = [];
		foreach ($requirements as $key => $requirement) {
			if (!$requirement["check"]) {
				$errors[] = "{$key}: required {$requirement["minimum"]}, found {$requirement["current"]}";
			}
		}

		if (!empty($errors)) {
			throw new \Exception(
				"System requirements not met: " . implode("; ", $errors)
			);
		}
	}

	protected function checkDiskSpace()
	{
		$freeSpace = disk_free_space(base_path());
		$requiredSpace = 100 * 1024 * 1024; // 100MB in bytes

		if ($freeSpace < $requiredSpace) {
			throw new \Exception(
				"Insufficient disk space. At least 100MB free space is required."
			);
		}

		$directoriesToCheck = [
			//base_path("Modules"),
			storage_path("app/public"),
			base_path("bootstrap/cache"),
		];

		foreach ($directoriesToCheck as $directory) {
			if (!is_writable($directory)) {
				throw new \Exception("Directory not writable: {$directory}");
			}
		}
	}

	protected function checkDependencies($packageName, $version = null)
	{
		try {
			$packagist = app(PackagistService::class);
			$packagInfo = $packagist->getPackage($packageName);

			if (!isset($packagInfo["require"])) {
				return;
			}

			dd($packagInfo["require"]);
		} catch (\Exception $e) {
		}
	}

	protected function memoryToBytes($memory)
	{
		$unit = strtolower(substr($memory, -1));
		$value = (int) $memory;

		switch ($unit) {
			case "g":
				return $value * 1024 * 1024 * 1024;
			case "m":
				return $value * 1024 * 1024;
			case "k":
				return $value * 1024;
			default:
				return $value;
		}
	}

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
	 * Check if a package corresponds to a local module
	 */
	public function isLocalModule($packageName)
	{
		$moduleName = $this->extractModuleNameFromPackage($packageName);
		return Module::has($moduleName);
	}
}
