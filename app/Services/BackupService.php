<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use ZipArchive;
use Exception;

class BackupService
{
	protected $backupDisk;

	public function __construct()
	{
		$this->backupDisk = Storage::disk(config("core.backup.disk", "local"));
	}

	/**
	 * Create comprehensive backup before module installation
	 */
	public function createBackup()
	{
		$timestamp = now()->format("Y-m-d_H-i-s");
		$backupId = uniqid();
		$backupPath = "module_backups/{$timestamp}_{$backupId}";

		try {
			// Create backup directory
			$this->backupDisk->makeDirectory($backupPath);

			// Backup database
			$this->backupDatabase($backupPath);

			// Backup module files
			$this->backupModuleFiles($backupPath);

			// Backup configuration files
			$this->backupConfiguration($backupPath);

			// Backup composer files
			$this->backupComposerFiles($backupPath);

			// Create backup manifest
			$this->createBackupManifest($backupPath);

			return $backupPath;
		} catch (Exception $e) {
			// Cleanup failed backup
			$this->cleanupFailedBackup($backupPath);
			throw new Exception("Backup creation failed: " . $e->getMessage());
		}
	}

	/**
	 * Restore backup after failed installation
	 */
	public function restoreBackup($backupPath)
	{
		if (!$this->backupDisk->exists($backupPath)) {
			throw new Exception("Backup not found: {$backupPath}");
		}

		try {
			// Restore database
			$this->restoreDatabase($backupPath);

			// Restore module files
			$this->restoreModuleFiles($backupPath);

			// Restore configuration files
			$this->restoreConfiguration($backupPath);

			// Restore composer files
			$this->restoreComposerFiles($backupPath);

			// Clear caches
			$this->clearCaches();

			// Cleanup backup after successful restore
			$this->backupDisk->deleteDirectory($backupPath);

			return true;
		} catch (Exception $e) {
			throw new Exception("Backup restoration failed: " . $e->getMessage());
		}
	}

	/**
	 * Backup database structure and data
	 */
	protected function backupDatabase($backupPath)
	{
		$connection = config("database.default");
		$databaseConfig = config("database.connections.{$connection}");

		switch ($databaseConfig["driver"]) {
			case "mysql":
				$this->backupMySQLDatabase($databaseConfig, $backupPath);
				break;
			case "pgsql":
				$this->backupPostgreSQLDatabase($databaseConfig, $backupPath);
				break;
			case "sqlite":
				$this->backupSQLiteDatabase($databaseConfig, $backupPath);
				break;
			default:
				throw new Exception(
					"Unsupported database driver: {$databaseConfig["driver"]}"
				);
		}
	}

	/**
	 * Backup MySQL database
	 */
	protected function backupMySQLDatabase($config, $backupPath)
	{
		$backupFile = "{$backupPath}/database.sql";
		$filePath = storage_path("app/{$backupFile}");

		$command = sprintf(
			"mysqldump --host=%s --port=%s --user=%s --password=%s %s > %s",
			escapeshellarg($config["host"]),
			escapeshellarg($config["port"] ?? "3306"),
			escapeshellarg($config["username"]),
			escapeshellarg($config["password"]),
			escapeshellarg($config["database"]),
			escapeshellarg($filePath)
		);

		$output = [];
		$returnCode = 0;

		exec($command, $output, $returnCode);

		if ($returnCode !== 0) {
			throw new Exception("MySQL backup failed with code: {$returnCode}");
		}

		// Verify backup file was created
		if (!file_exists($filePath) || filesize($filePath) === 0) {
			throw new Exception("MySQL backup file is empty or not created");
		}
	}

	/**
	 * Backup PostgreSQL database
	 */
	protected function backupPostgreSQLDatabase($config, $backupPath)
	{
		$backupFile = "{$backupPath}/database.sql";
		$filePath = storage_path("app/{$backupFile}");

		putenv("PGPASSWORD={$config["password"]}");

		$command = sprintf(
			"pg_dump --host=%s --port=%s --username=%s --dbname=%s --file=%s --no-password",
			escapeshellarg($config["host"]),
			escapeshellarg($config["port"] ?? "5432"),
			escapeshellarg($config["username"]),
			escapeshellarg($config["database"]),
			escapeshellarg($filePath)
		);

		$output = [];
		$returnCode = 0;

		exec($command, $output, $returnCode);

		if ($returnCode !== 0) {
			throw new Exception("PostgreSQL backup failed with code: {$returnCode}");
		}
	}

	/**
	 * Backup SQLite database
	 */
	protected function backupSQLiteDatabase($config, $backupPath)
	{
		$databasePath = $config["database"];

		if (!file_exists($databasePath)) {
			throw new Exception("SQLite database file not found: {$databasePath}");
		}

		$backupFile = "{$backupPath}/database.sqlite";
		$this->backupDisk->put($backupFile, file_get_contents($databasePath));
	}

	/**
	 * Backup module files
	 */
	protected function backupModuleFiles($backupPath)
	{
		$modulesPath = base_path("Modules");

		if (!File::exists($modulesPath)) {
			return;
		}

		$zip = new ZipArchive();
		$zipPath = storage_path("app/{$backupPath}/modules.zip");

		if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
			$files = File::allFiles($modulesPath);

			foreach ($files as $file) {
				$relativePath = str_replace(
					base_path() . "/",
					"",
					$file->getRealPath()
				);
				$zip->addFile($file->getRealPath(), $relativePath);
			}

			$zip->close();
		} else {
			throw new Exception("Failed to create modules backup zip");
		}
	}

	/**
	 * Backup configuration files
	 */
	protected function backupConfiguration($backupPath)
	{
		$configFiles = [
			"modules.php",
			"app.php",
			"database.php",
			"cache.php",
			"queue.php",
		];

		foreach ($configFiles as $configFile) {
			$sourcePath = config_path($configFile);

			if (File::exists($sourcePath)) {
				$content = File::get($sourcePath);
				$this->backupDisk->put("{$backupPath}/config/{$configFile}", $content);
			}
		}

		// Backup .env file
		$envPath = base_path(".env");
		if (File::exists($envPath)) {
			$content = File::get($envPath);
			$this->backupDisk->put("{$backupPath}/config/.env", $content);
		}
	}

	/**
	 * Backup composer files
	 */
	protected function backupComposerFiles($backupPath)
	{
		$composerFiles = ["composer.json", "composer.lock"];

		foreach ($composerFiles as $file) {
			$sourcePath = base_path($file);

			if (File::exists($sourcePath)) {
				$content = File::get($sourcePath);
				$this->backupDisk->put("{$backupPath}/composer/{$file}", $content);
			}
		}
	}

	/**
	 * Create backup manifest
	 */
	protected function createBackupManifest($backupPath)
	{
		$manifest = [
			"timestamp" => now()->toISOString(),
			"backup_id" => uniqid(),
			"application" => [
				"name" => config("app.name"),
				"env" => config("app.env"),
				"url" => config("app.url"),
			],
			"laravel_version" => app()->version(),
			"php_version" => PHP_VERSION,
			"database" => [
				"driver" => config("database.default"),
				"version" => $this->getDatabaseVersion(),
			],
			"modules" => $this->getInstalledModules(),
			"files" => [
				"database" => $this->backupDisk->exists("{$backupPath}/database.sql")
					? "yes"
					: "no",
				"modules" => $this->backupDisk->exists("{$backupPath}/modules.zip")
					? "yes"
					: "no",
				"config" => "yes",
				"composer" => "yes",
			],
		];

		$this->backupDisk->put(
			"{$backupPath}/manifest.json",
			json_encode($manifest, JSON_PRETTY_PRINT)
		);
	}

	/**
	 * Get database version
	 */
	protected function getDatabaseVersion()
	{
		try {
			$result = DB::select(DB::raw("SELECT VERSION() as version"));
			return $result[0]->version ?? "unknown";
		} catch (Exception $e) {
			return "unknown";
		}
	}

	/**
	 * Get installed modules
	 */
	protected function getInstalledModules()
	{
		try {
			$modules = \Nwidart\Modules\Facades\Module::all();
			$moduleList = [];

			foreach ($modules as $module) {
				$moduleList[$module->getName()] = [
					"enabled" => $module->isEnabled(),
					"version" => $module->get("version", "1.0.0"),
				];
			}

			return $moduleList;
		} catch (Exception $e) {
			return [];
		}
	}

	/**
	 * Restore database from backup
	 */
	protected function restoreDatabase($backupPath)
	{
		$connection = config("database.default");
		$databaseConfig = config("database.connections.{$connection}");

		// First, backup current database state
		$currentBackup = $this->createTemporaryBackup();

		try {
			switch ($databaseConfig["driver"]) {
				case "mysql":
					$this->restoreMySQLDatabase($databaseConfig, $backupPath);
					break;
				case "pgsql":
					$this->restorePostgreSQLDatabase($databaseConfig, $backupPath);
					break;
				case "sqlite":
					$this->restoreSQLiteDatabase($databaseConfig, $backupPath);
					break;
			}
		} catch (Exception $e) {
			// Restore original database state
			$this->restoreTemporaryBackup($currentBackup);
			throw $e;
		} finally {
			// Cleanup temporary backup
			$this->cleanupTemporaryBackup($currentBackup);
		}
	}

	/**
	 * Restore MySQL database
	 */
	protected function restoreMySQLDatabase($config, $backupPath)
	{
		$backupFile = "{$backupPath}/database.sql";
		$filePath = storage_path("app/{$backupFile}");

		if (!$this->backupDisk->exists($backupFile)) {
			throw new Exception("Database backup file not found");
		}

		$command = sprintf(
			"mysql --host=%s --port=%s --user=%s --password=%s %s < %s",
			escapeshellarg($config["host"]),
			escapeshellarg($config["port"] ?? "3306"),
			escapeshellarg($config["username"]),
			escapeshellarg($config["password"]),
			escapeshellarg($config["database"]),
			escapeshellarg($filePath)
		);

		$output = [];
		$returnCode = 0;

		exec($command, $output, $returnCode);

		if ($returnCode !== 0) {
			throw new Exception("MySQL restore failed with code: {$returnCode}");
		}
	}

	/**
	 * Restore module files
	 */
	protected function restoreModuleFiles($backupPath)
	{
		$zipFile = "{$backupPath}/modules.zip";

		if (!$this->backupDisk->exists($zipFile)) {
			return;
		}

		$zipPath = storage_path("app/{$zipFile}");
		$extractPath = base_path("Modules");

		// Remove current modules directory
		if (File::exists($extractPath)) {
			File::deleteDirectory($extractPath);
		}

		// Extract backup
		$zip = new ZipArchive();
		if ($zip->open($zipPath) === true) {
			$zip->extractTo($extractPath);
			$zip->close();
		} else {
			throw new Exception("Failed to extract modules backup");
		}
	}

	/**
	 * Restore configuration files
	 */
	protected function restoreConfiguration($backupPath)
	{
		$configBackupPath = "{$backupPath}/config";

		if (!$this->backupDisk->exists($configBackupPath)) {
			return;
		}

		$configFiles = $this->backupDisk->files($configBackupPath);

		foreach ($configFiles as $configFile) {
			$fileName = basename($configFile);
			$content = $this->backupDisk->get($configFile);

			if ($fileName === ".env") {
				File::put(base_path(".env"), $content);
			} else {
				File::put(config_path($fileName), $content);
			}
		}
	}

	/**
	 * Restore composer files
	 */
	protected function restoreComposerFiles($backupPath)
	{
		$composerBackupPath = "{$backupPath}/composer";

		if (!$this->backupDisk->exists($composerBackupPath)) {
			return;
		}

		$composerFiles = $this->backupDisk->files($composerBackupPath);

		foreach ($composerFiles as $composerFile) {
			$fileName = basename($composerFile);
			$content = $this->backupDisk->get($composerFile);
			File::put(base_path($fileName), $content);
		}
	}

	/**
	 * Create temporary backup for rollback during restore
	 */
	protected function createTemporaryBackup()
	{
		$tempBackupPath = "temp_restore_backup/" . uniqid();
		$this->backupDatabase($tempBackupPath);
		return $tempBackupPath;
	}

	/**
	 * Restore temporary backup
	 */
	protected function restoreTemporaryBackup($tempBackupPath)
	{
		if ($this->backupDisk->exists($tempBackupPath)) {
			$this->restoreDatabase($tempBackupPath);
		}
	}

	/**
	 * Cleanup temporary backup
	 */
	protected function cleanupTemporaryBackup($tempBackupPath)
	{
		if ($this->backupDisk->exists($tempBackupPath)) {
			$this->backupDisk->deleteDirectory($tempBackupPath);
		}
	}

	/**
	 * Clear application caches
	 */
	protected function clearCaches()
	{
		Artisan::call("cache:clear");
		Artisan::call("config:clear");
		Artisan::call("route:clear");
		Artisan::call("view:clear");
	}

	/**
	 * Cleanup failed backup
	 */
	protected function cleanupFailedBackup($backupPath)
	{
		if ($this->backupDisk->exists($backupPath)) {
			$this->backupDisk->deleteDirectory($backupPath);
		}
	}

	/**
	 * List available backups
	 */
	public function listBackups()
	{
		$backups = [];

		if ($this->backupDisk->exists("module_backups")) {
			$directories = $this->backupDisk->directories("module_backups");

			foreach ($directories as $directory) {
				if ($this->backupDisk->exists("{$directory}/manifest.json")) {
					$manifest = json_decode(
						$this->backupDisk->get("{$directory}/manifest.json"),
						true
					);
					$backups[] = [
						"path" => $directory,
						"timestamp" => $manifest["timestamp"] ?? "",
						"backup_id" => $manifest["backup_id"] ?? "",
						"application" => $manifest["application"]["name"] ?? "",
					];
				}
			}
		}

		// Sort by timestamp descending
		usort($backups, function ($a, $b) {
			return strtotime($b["timestamp"]) - strtotime($a["timestamp"]);
		});

		return $backups;
	}

	/**
	 * Cleanup old backups (keep only last 10)
	 */
	public function cleanupOldBackups()
	{
		$backups = $this->listBackups();

		if (count($backups) > 10) {
			$backupsToDelete = array_slice($backups, 10);

			foreach ($backupsToDelete as $backup) {
				$this->backupDisk->deleteDirectory($backup["path"]);
			}

			return count($backupsToDelete);
		}

		return 0;
	}
}
