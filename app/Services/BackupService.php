<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use Spatie\Backup\Tasks\Backup\BackupJobFactory;
use Spatie\Backup\BackupDestination\BackupDestination;
use ZipArchive;
use Exception;

class BackupService
{
	protected $backupDisk;

	public function __construct()
	{
		$this->backupDisk = "local";
	}

	/**
	 * Create comprehensive backup before module installation
	 */
	public function createBackup($backupName = null)
	{
		try {
			$timestamp = now()->format("Y-m-d_H-i-s");
			$backupId = $backupName ?: "module_installation_{$timestamp}";
			Log::info("Starting backup with spatie/laravel-backup: {$backupId}");

			$backupJob = BackupJobFactory::createFromConfig(config("backup"));
			$backupJob->setFilename($backupId . ".zip");
			$backupJob->run();

			$backupPath = $this->getLatestBackupPath();
			Log::info("Backup completed successfuly: {$backupPath}");

			return $backupPath;
		} catch (Exception $e) {
			Log::error("Spatie backup failed: " . $e->getMessage());
			throw new Exception("Backup creation failed: " . $e->getMessage());
		}
	}

	protected function getLatestBackupPath()
	{
		$backupDestination = BackupDestination::create(
			"local",
			config("backup.backup.name")
		);
		$backups = $backupDestination->backups();

		if ($backups->isEmpty()) {
			throw new Exception("No backups found.");
		}

		$latestBackup = $backups->newest();
		return $latestBackup->path();
	}

	/**
	 * Restore backup - Note: spatie/laravel-backup tidak menyediakan restore otomatis
	 * Kita perlu implementasi manual atau menggunakan package tambahan
	 */
	public function restoreBackup($backupPath)
	{
		try {
			Log::info("Starting restore from: {$backupPath}");

			// Karena spatie/laravel-backup tidak menyediakan restore otomatis,
			// kita perlu implementasi manual restore process
			$this->manualRestore($backupPath);

			Log::info("Restore completed successfully");

			return true;
		} catch (Exception $e) {
			Log::error("Restore failed: " . $e->getMessage());
			throw new Exception("Backup restoration failed: " . $e->getMessage());
		}
	}

	/**
	 * Manual restore process untuk module installation
	 */
	protected function manualRestore($backupPath)
	{
		// Extract backup zip
		$extractPath = storage_path("app/restore-temp/" . uniqid());
		if (!is_dir($extractPath)) {
			mkdir($extractPath, 0755, true);
		}

		$zip = new ZipArchive();
		if ($zip->open($backupPath) === true) {
			$zip->extractTo($extractPath);
			$zip->close();
		} else {
			throw new Exception("Failed to extract backup file");
		}

		// Restore database dari dump file
		$this->restoreDatabaseFromDump($extractPath);

		// Restore module files
		$this->restoreModuleFiles($extractPath);

		// Cleanup temporary files
		$this->deleteDirectory($extractPath);
	}

	/**
	 * Restore database dari SQL dump
	 */
	protected function restoreDatabaseFromDump($extractPath)
	{
		$databaseDumpPath = $this->findDatabaseDump($extractPath);

		if (!$databaseDumpPath) {
			throw new Exception("Database dump not found in backup");
		}

		$databaseConfig = config("database.connections.mysql");
		$command = sprintf(
			"mysql --host=%s --port=%s --user=%s --password=%s %s < %s",
			escapeshellarg($databaseConfig["host"]),
			escapeshellarg($databaseConfig["port"] ?? "3306"),
			escapeshellarg($databaseConfig["username"]),
			escapeshellarg($databaseConfig["password"]),
			escapeshellarg($databaseConfig["database"]),
			escapeshellarg($databaseDumpPath)
		);

		exec($command, $output, $returnCode);

		if ($returnCode !== 0) {
			throw new Exception("Database restore failed with code: {$returnCode}");
		}
	}

	/**
	 * Restore module files
	 */
	protected function restoreModuleFiles($extractPath)
	{
		$modulesBackupPath = $extractPath . "/Modules";

		if (is_dir($modulesBackupPath)) {
			// Hapus Modules directory yang current
			$currentModulesPath = base_path("Modules");
			if (is_dir($currentModulesPath)) {
				$this->deleteDirectory($currentModulesPath);
			}

			// Copy backup Modules directory
			$this->copyDirectory($modulesBackupPath, $currentModulesPath);
		}
	}

	/**
	 * Cari database dump file dalam extracted backup
	 */
	protected function findDatabaseDump($extractPath)
	{
		$files = glob($extractPath . "/*.sql");
		return $files[0] ?? null;
	}

	/**
	 * Utility function untuk delete directory
	 */
	protected function deleteDirectory($dir)
	{
		if (!is_dir($dir)) {
			return;
		}

		$files = array_diff(scandir($dir), [".", ".."]);
		foreach ($files as $file) {
			$path = $dir . "/" . $file;
			is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
		}
		rmdir($dir);
	}

	/**
	 * Utility function untuk copy directory
	 */
	protected function copyDirectory($source, $destination)
	{
		if (!is_dir($destination)) {
			mkdir($destination, 0755, true);
		}

		$files = array_diff(scandir($source), [".", ".."]);
		foreach ($files as $file) {
			$srcPath = $source . "/" . $file;
			$destPath = $destination . "/" . $file;

			if (is_dir($srcPath)) {
				$this->copyDirectory($srcPath, $destPath);
			} else {
				copy($srcPath, $destPath);
			}
		}
	}

	/**
	 * Cleanup old backups
	 */
	public function cleanupOldBackups()
	{
		try {
			Artisan::call("backup:clean");
			Log::info("Old backups cleaned successfully");
			return true;
		} catch (Exception $e) {
			Log::error("Backup cleanup failed: " . $e->getMessage());
			return false;
		}
	}
}
