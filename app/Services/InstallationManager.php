<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class InstallationManager
{
	protected $advancedPostInstallService;

	public function __construct(
		AdvancedPostInstallService $advancedPostInstallService
	) {
		$this->advancedPostInstallService = $advancedPostInstallService;
	}

	/**
	 * Comprehensive package installation with rollback support
	 */
	public function installPackageWithRollback($packageName, $version = "*")
	{
		$backupPoint = $this->createBackupPoint($packageName);

		try {
			// Step 1: Composer Installation
			$composerResult = $this->composerRequire($packageName, $version);
			if (!$composerResult["success"]) {
				throw new \Exception(
					"Composer installation failed: " . $composerResult["message"]
				);
			}

			// Step 2: Advanced Post-Installation Processing
			$postInstallResult = $this->advancedPostInstallService->processAdvancedInstallation(
				$packageName,
				$version
			);

			if (!$postInstallResult["success"]) {
				// Rollback on post-installation failure
				$this->rollbackInstallation($packageName, $backupPoint);
				throw new \Exception(
					"Post-installation failed: " . $postInstallResult["message"]
				);
			}

			// Step 3: Final Verification
			$verificationResult = $this->verifyInstallation($packageName);
			if (!$verificationResult["success"]) {
				$this->rollbackInstallation($packageName, $backupPoint);
				throw new \Exception(
					"Installation verification failed: " . $verificationResult["message"]
				);
			}

			return [
				"success" => true,
				"message" => "Package {$packageName} installed successfully",
				"composer_result" => $composerResult,
				"post_install_result" => $postInstallResult,
				"verification_result" => $verificationResult,
				"backup_point" => $backupPoint,
			];
		} catch (\Exception $e) {
			$this->rollbackInstallation($packageName, $backupPoint);

			return [
				"success" => false,
				"message" => $e->getMessage(),
				"backup_point" => $backupPoint,
			];
		}
	}

	/**
	 * Create backup point before installation
	 */
	private function createBackupPoint($packageName)
	{
		$backupId = uniqid("install_{$packageName}_");
		$backupPoint = [
			"id" => $backupId,
			"timestamp" => now()->toISOString(),
			"package" => $packageName,
			"database_tables" => $this->backupRelevantTables($packageName),
			"config_files" => $this->backupConfigFiles($packageName),
		];

		Log::info("Created backup point: {$backupId} for package {$packageName}");
		return $backupPoint;
	}

	/**
	 * Backup relevant database tables
	 */
	private function backupRelevantTables($packageName)
	{
		// Implementation would backup tables that might be affected
		// This is a simplified version
		$moduleName = $this->extractModuleNameFromPackage($packageName);
		$tables = $this->advancedPostInstallService->getModuleTables($moduleName);

		return [
			"tables" => $tables,
			"backup_time" => now()->toISOString(),
		];
	}

	/**
	 * Backup configuration files
	 */
	private function backupConfigFiles($packageName)
	{
		$configFiles = [
			"config/modules.php",
			"bootstrap/cache/packages.php",
			"bootstrap/cache/services.php",
		];

		$backups = [];
		foreach ($configFiles as $configFile) {
			if (File::exists(base_path($configFile))) {
				$backupPath = storage_path(
					"backups/{$packageName}/" . basename($configFile)
				);
				File::ensureDirectoryExists(dirname($backupPath));
				File::copy(base_path($configFile), $backupPath);
				$backups[] = $backupPath;
			}
		}

		return $backups;
	}

	/**
	 * Rollback installation to backup point
	 */
	private function rollbackInstallation($packageName, $backupPoint)
	{
		Log::warning(
			"Rolling back installation of {$packageName} to backup point {$backupPoint["id"]}"
		);

		try {
			// Step 1: Composer remove
			$this->composerRemove($packageName);

			// Step 2: Restore database from backup
			$this->restoreDatabaseBackup($backupPoint);

			// Step 3: Restore configuration files
			$this->restoreConfigFiles($backupPoint);

			// Step 4: Clear caches
			Artisan::call("optimize:clear");

			Log::info("Rollback completed for package {$packageName}");
		} catch (\Exception $e) {
			Log::error(
				"Rollback failed for package {$packageName}: " . $e->getMessage()
			);
			throw new \Exception("Rollback failed: " . $e->getMessage());
		}
	}

	/**
	 * Verify installation success
	 */
	private function verifyInstallation($packageName)
	{
		$moduleName = $this->extractModuleNameFromPackage($packageName);

		$checks = [
			"module_exists" => Module::has($moduleName),
			"module_enabled" => Module::has($moduleName)
				? Module::find($moduleName)->isEnabled()
				: false,
			"migrations_ran" => $this->checkMigrations($moduleName),
			"routes_registered" => $this->checkRoutes($moduleName),
		];

		$allPassed = !in_array(false, $checks, true);

		return [
			"success" => $allPassed,
			"checks" => $checks,
			"message" => $allPassed
				? "All verification checks passed"
				: "Some verification checks failed",
		];
	}

	/**
	 * Check if migrations ran successfully
	 */
	private function checkMigrations($moduleName)
	{
		try {
			$output = [];
			Artisan::call(
				"module:migrate:status",
				["module" => $moduleName],
				$output
			);
			$statusOutput = implode('\n', $output);

			// Check if any migrations are pending
			return !str_contains($statusOutput, "No");
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Check if routes are registered
	 */
	private function checkRoutes($moduleName)
	{
		try {
			$routes = app("router")
				->getRoutes()
				->getRoutes();
			$moduleRoutes = array_filter($routes, function ($route) use (
				$moduleName
			) {
				return str_contains($route->getActionName(), $moduleName);
			});

			return count($moduleRoutes) > 0;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Execute composer require
	 */
	private function composerRequire($packageName, $version)
	{
		$output = [];
		$returnCode = 0;

		$command = "composer require {$packageName}:{$version} --no-dev -n --no-scripts";
		exec($command, $output, $returnCode);

		return [
			"success" => $returnCode === 0,
			"message" =>
				$returnCode === 0
					? "Composer require successful"
					: implode('\n', $output),
			"output" => $output,
		];
	}

	/**
	 * Execute composer remove
	 */
	private function composerRemove($packageName)
	{
		$output = [];
		$returnCode = 0;

		$command = "composer remove {$packageName} --no-dev -n";
		exec($command, $output, $returnCode);

		return $returnCode === 0;
	}

	/**
	 * Extract module name from package name
	 */
	private function extractModuleNameFromPackage($packageName)
	{
		$parts = explode("/", $packageName);
		$name = end($parts);
		return str_replace(" ", "", ucwords(str_replace("-", " ", $name)));
	}
}
