<?php

namespace Modules\Core\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Core\Events\ModuleInstallationFailed;
use Modules\Core\Services\BackupService;
use Modules\Core\Services\ComposerService;
use Modules\Core\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Nwidart\Modules\Facades\Module;

class HandleInstallationFailure implements ShouldQueue
{
	use InteractsWithQueue;

	public $tries = 2;
	public $timeout = 600; // 10 minutes

	protected $backupService;
	protected $composerService;
	protected $notificationService;

	public function __construct(
		BackupService $backupService,
		ComposerService $composerService,
		NotificationService $notificationService
	) {
		$this->backupService = $backupService;
		$this->composerService = $composerService;
		$this->notificationService = $notificationService;
	}

	/**
	 * Handle the event when module installation fails
	 */
	public function handle(ModuleInstallationFailed $event): void
	{
		Log::error(
			"Module installation failed: {$event->packageName}@{$event->version} - {$event->error}"
		);

		try {
			// Step 1: Attempt to remove the failed package via composer
			$this->removeFailedPackage($event->packageName);

			// Step 2: Restore from backup if available
			$this->restoreFromBackup($event->backupPath);

			// Step 3: Cleanup any residual files
			$this->cleanupResidualFiles($event->packageName);

			// Step 4: Clear caches and re-optimize
			$this->clearApplicationCaches();

			// Step 5: Send failure notification
			$this->sendFailureNotification($event);

			Log::info(
				"Successfully handled installation failure for: {$event->packageName}"
			);
		} catch (\Exception $e) {
			Log::error(
				"Error handling installation failure for {$event->packageName}: " .
					$e->getMessage()
			);

			// Even if cleanup fails, still send notification
			$this->sendEmergencyNotification($event, $e->getMessage());
		}
	}

	/**
	 * Remove failed package via composer
	 */
	protected function removeFailedPackage($packageName)
	{
		try {
			Log::info("Attempting to remove failed package: {$packageName}");

			$this->composerService->removePackage($packageName);

			Log::info("Successfully removed package: {$packageName}");
		} catch (\Exception $e) {
			Log::warning(
				"Failed to remove package via composer: {$packageName} - " .
					$e->getMessage()
			);

			// Fallback: Manual cleanup
			$this->manualPackageCleanup($packageName);
		}
	}

	/**
	 * Manual cleanup if composer remove fails
	 */
	protected function manualPackageCleanup($packageName)
	{
		try {
			$moduleName = $this->extractModuleName($packageName);

			// Remove from vendor directory
			$vendorPath = base_path("vendor/{$packageName}");
			if (File::exists($vendorPath)) {
				File::deleteDirectory($vendorPath);
				Log::info("Manually removed vendor directory: {$vendorPath}");
			}

			// Remove from modules directory
			$module = Module::find($moduleName);
			if ($module) {
				$modulePath = $module->getPath();
				File::deleteDirectory($modulePath);
				Log::info("Manually removed module directory: {$modulePath}");
			}

			// Remove from composer autoload
			$this->cleanupComposerAutoload($packageName);
		} catch (\Exception $e) {
			Log::error(
				"Manual package cleanup failed for {$packageName}: " . $e->getMessage()
			);
			throw new \Exception("Manual cleanup failed: " . $e->getMessage());
		}
	}

	/**
	 * Cleanup composer autoload files
	 */
	protected function cleanupComposerAutoload($packageName)
	{
		try {
			// Remove from composer.json (if it was added)
			$composerPath = base_path("composer.json");
			if (File::exists($composerPath)) {
				$composer = json_decode(File::get($composerPath), true);

				if (isset($composer["require"][$packageName])) {
					unset($composer["require"][$packageName]);
					File::put(
						$composerPath,
						json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
					);
					Log::info("Removed package from composer.json: {$packageName}");
				}
			}

			// Regenerate autoload files
			Artisan::call("optimize:clear");
			$this->composerService->dumpAutoload();
		} catch (\Exception $e) {
			Log::warning("Composer autoload cleanup failed: " . $e->getMessage());
		}
	}

	/**
	 * Restore application from backup
	 */
	protected function restoreFromBackup($backupPath)
	{
		if (!$backupPath) {
			Log::warning("No backup path provided for restoration");
			return;
		}

		try {
			Log::info("Attempting to restore from backup: {$backupPath}");

			$this->backupService->restoreBackup($backupPath);

			Log::info("Successfully restored from backup: {$backupPath}");
		} catch (\Exception $e) {
			Log::error("Backup restoration failed: " . $e->getMessage());

			// If backup restoration fails, try to restore composer files only
			$this->restoreComposerFilesOnly($backupPath);

			throw new \Exception("Backup restoration failed: " . $e->getMessage());
		}
	}

	/**
	 * Restore only composer files as fallback
	 */
	protected function restoreComposerFilesOnly($backupPath)
	{
		try {
			Log::info("Attempting composer files restoration from: {$backupPath}");

			// This would be implemented in BackupService
			$this->backupService->restoreComposerFiles($backupPath);

			// Run composer install to restore dependencies
			$this->composerService->install();

			Log::info("Composer files restoration completed");
		} catch (\Exception $e) {
			Log::error("Composer files restoration failed: " . $e->getMessage());
			throw new \Exception("Composer restoration failed: " . $e->getMessage());
		}
	}

	/**
	 * Cleanup any residual files
	 */
	protected function cleanupResidualFiles($packageName)
	{
		$moduleName = $this->extractModuleName($packageName);

		// Cleanup potential residual directories
		$residualPaths = [
			base_path("vendor/{$packageName}"),
			base_path("Modules/{$moduleName}"),
			storage_path("app/modules/{$moduleName}"),
			public_path("modules/{$moduleName}"),
		];

		foreach ($residualPaths as $path) {
			if (File::exists($path)) {
				try {
					File::deleteDirectory($path);
					Log::info("Cleaned up residual path: {$path}");
				} catch (\Exception $e) {
					Log::warning(
						"Failed to cleanup residual path {$path}: " . $e->getMessage()
					);
				}
			}
		}
	}

	/**
	 * Clear application caches
	 */
	protected function clearApplicationCaches()
	{
		try {
			Artisan::call("optimize:clear");
			Artisan::call("module:discover");
			Artisan::call("config:cache");
			Artisan::call("route:cache");

			Log::info("Application caches cleared and re-optimized");
		} catch (\Exception $e) {
			Log::warning("Cache clearing failed: " . $e->getMessage());
		}
	}

	/**
	 * Send failure notification
	 */
	protected function sendFailureNotification(ModuleInstallationFailed $event)
	{
		try {
			$this->notificationService->sendInstallationFailureNotification(
				$event->packageName,
				$event->version,
				$event->error,
				$event->backupPath
			);

			Log::info("Failure notification sent for: {$event->packageName}");
		} catch (\Exception $e) {
			Log::error("Failed to send failure notification: " . $e->getMessage());
		}
	}

	/**
	 * Send emergency notification when everything fails
	 */
	protected function sendEmergencyNotification(
		ModuleInstallationFailed $event,
		$cleanupError
	) {
		try {
			$this->notificationService->sendEmergencyFailureNotification(
				$event->packageName,
				$event->version,
				$event->error,
				$cleanupError
			);
		} catch (\Exception $e) {
			Log::error("Emergency notification also failed: " . $e->getMessage());
		}
	}

	/**
	 * Extract module name from package name
	 */
	protected function extractModuleName($packageName)
	{
		$parts = explode("/", $packageName);
		$moduleName = end($parts);

		// Convert kebab-case to StudlyCase
		$moduleName = str_replace(
			" ",
			"",
			ucwords(str_replace("-", " ", $moduleName))
		);

		return $moduleName;
	}

	/**
	 * Handle job failure
	 */
	public function failed(ModuleInstallationFailed $event, $exception)
	{
		Log::emergency(
			"HandleInstallationFailure job failed completely for {$event->packageName}: " .
				$exception->getMessage()
		);

		// Send emergency notification
		$this->sendEmergencyNotification($event, $exception->getMessage());
	}
}
