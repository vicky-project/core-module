<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Nwidart\Modules\Facades\Module;
use Illuminate\Support\Str;

class AdvancedPostInstallService
{
	/**
	 * Comprehensive installation processor
	 */
	public function processAdvancedInstallation($packageName, $version = null)
	{
		$startTime = microtime(true);
		$executionLog = [];

		try {
			$moduleName = $this->extractModuleNameFromPackage($packageName);

			// Check if module exists
			if (!Module::has($moduleName)) {
				throw new \Exception(
					"Module {$moduleName} not found after installation"
				);
			}

			$module = Module::find($moduleName);
			$modulePath = $module->getPath();

			// Get installation configuration with fallbacks
			$config = $this->getComprehensiveInstallConfig($modulePath, $packageName);

			$executionLog[] = $this->logStep(
				"start",
				"Starting installation for {$packageName}"
			);

			// Phase 1: Pre-Installation Validation
			$executionLog = array_merge(
				$executionLog,
				$this->executePhase(
					$config["validation"] ?? [],
					"validation",
					$moduleName,
					$modulePath
				)
			);

			// Phase 2: System Preparation
			$executionLog = array_merge(
				$executionLog,
				$this->executePhase(
					$config["pre_install"] ?? [],
					"pre_install",
					$moduleName,
					$modulePath
				)
			);

			// Phase 3: Core Installation
			$executionLog = array_merge(
				$executionLog,
				$this->executePhase(
					$config["install"] ?? [],
					"install",
					$moduleName,
					$modulePath
				)
			);

			// Phase 4: Database Optimization (MediaWiki-style)
			$executionLog = array_merge(
				$executionLog,
				$this->executePhase(
					$config["database_optimization"] ?? [],
					"database_optimization",
					$moduleName,
					$modulePath
				)
			);

			// Phase 5: Post-Installation
			$executionLog = array_merge(
				$executionLog,
				$this->executePhase(
					$config["post_install"] ?? [],
					"post_install",
					$moduleName,
					$modulePath
				)
			);

			// Phase 6: Cache & Performance Optimization
			$executionLog = array_merge(
				$executionLog,
				$this->executePhase(
					$config["optimization"] ?? [],
					"optimization",
					$moduleName,
					$modulePath
				)
			);

			$totalTime = round(microtime(true) - $startTime, 2);
			$executionLog[] = $this->logStep(
				"complete",
				"Installation completed in {$totalTime}s"
			);

			return [
				"success" => true,
				"message" => "Module {$moduleName} installed successfully",
				"execution_time" => $totalTime,
				"total_steps" => count($executionLog),
				"log" => $executionLog,
			];
		} catch (\Exception $e) {
			$executionLog[] = $this->logStep("error", $e->getMessage());

			return [
				"success" => false,
				"message" => "Installation failed: " . $e->getMessage(),
				"execution_time" => round(microtime(true) - $startTime, 2),
				"log" => $executionLog,
			];
		}
	}

	/**
	 * Get comprehensive installation configuration
	 */
	private function getComprehensiveInstallConfig($modulePath, $packageName)
	{
		$configFiles = [
			"module-install.json",
			"installation.json",
			"composer.json",
			"package.json",
		];

		foreach ($configFiles as $configFile) {
			$configPath = $modulePath . "/" . $configFile;

			if (File::exists($configPath)) {
				$config = json_decode(File::get($configPath), true);

				if (
					json_last_error() === JSON_ERROR_NONE &&
					isset($config["installation"])
				) {
					return $config["installation"];
				}
			}
		}

		// Return optimized default configuration
		return $this->getOptimizedDefaultConfig();
	}

	/**
	 * Optimized default configuration based on best practices
	 */
	private function getOptimizedDefaultConfig()
	{
		return [
			"validation" => [
				[
					"type" => "system_check",
					"checks" => ["php_version", "extensions", "permissions"],
					"description" => "Validate system requirements",
				],
			],
			"pre_install" => [
				[
					"type" => "artisan",
					"command" => "cache:clear",
					"description" => "Clear application cache",
				],
				[
					"type" => "artisan",
					"command" => "view:clear",
					"description" => "Clear view cache",
				],
			],
			"install" => [
				[
					"type" => "migrate",
					"command" => "module:migrate",
					"description" => "Run database migrations",
				],
				[
					"type" => "publish",
					"command" => "module:publish",
					"description" => "Publish module assets and configuration",
				],
				[
					"type" => "enable",
					"command" => "module:enable",
					"description" => "Enable the module",
				],
			],
			"database_optimization" => [
				[
					"type" => "artisan",
					"command" => "db:seed",
					"description" => "Seed database with initial data",
				],
			],
			"post_install" => [
				[
					"type" => "artisan",
					"command" => "config:cache",
					"description" => "Cache configuration files",
				],
				[
					"type" => "artisan",
					"command" => "route:cache",
					"description" => "Cache routes",
				],
			],
			"optimization" => [
				[
					"type" => "artisan",
					"command" => "optimize:clear",
					"description" => "Clear and re-optimize application",
				],
			],
		];
	}

	/**
	 * Execute a phase of installation steps
	 */
	private function executePhase($steps, $phaseName, $moduleName, $modulePath)
	{
		$phaseLog = [];
		$phaseLog[] = $this->logStep("phase_start", "Starting phase: {$phaseName}");

		foreach ($steps as $index => $step) {
			$stepNumber = $index + 1;
			$result = $this->executeAdvancedStep(
				$step,
				$moduleName,
				$modulePath,
				$phaseName,
				$stepNumber
			);
			$phaseLog[] = $result;

			if (!$result["success"]) {
				throw new \Exception(
					"Phase {$phaseName} failed at step {$stepNumber}: {$result["message"]}"
				);
			}
		}

		$phaseLog[] = $this->logStep(
			"phase_complete",
			"Completed phase: {$phaseName}"
		);
		return $phaseLog;
	}

	/**
	 * Execute advanced installation step with comprehensive support
	 */
	private function executeAdvancedStep(
		$step,
		$moduleName,
		$modulePath,
		$phase,
		$stepNumber
	) {
		$startTime = microtime(true);

		try {
			$stepType = $step["type"] ?? "unknown";
			$description = $step["description"] ?? "No description";

			Log::info(
				"Executing {$phase} step {$stepNumber}: {$stepType} - {$description}"
			);

			$result = match ($stepType) {
				"system_check" => $this->executeSystemCheck($step),
				"migrate" => $this->executeMigration($step, $moduleName),
				"seed" => $this->executeSeeder($step, $moduleName),
				"publish" => $this->executePublish($step),
				"enable", "disable" => $this->executeModuleState($step, $moduleName),
				"artisan" => $this->executeArtisan($step),
				"composer" => $this->executeComposer($step),
				"file_operation" => $this->executeFileOperation($step, $modulePath),
				"database_optimization" => $this->executeDatabaseOptimization(
					$step,
					$moduleName
				),
				"cache_optimization" => $this->executeCacheOptimization($step),
				"custom" => $this->executeCustomHandler($step, $moduleName),
				"event" => $this->executeEvent($step, $moduleName),
				"hook" => $this->executeHook($step, $moduleName),
				default => $this->executeUnknownStep($step),
			};

			$executionTime = round(microtime(true) - $startTime, 2);

			return [
				"success" => true,
				"phase" => $phase,
				"step" => $stepNumber,
				"type" => $stepType,
				"description" => $description,
				"execution_time" => $executionTime,
				"message" => $result["message"] ?? "Step executed successfully",
			];
		} catch (\Exception $e) {
			$executionTime = round(microtime(true) - $startTime, 2);

			return [
				"success" => false,
				"phase" => $phase,
				"step" => $stepNumber,
				"type" => $stepType ?? "unknown",
				"description" => $description ?? "No description",
				"execution_time" => $executionTime,
				"message" => $e->getMessage(),
			];
		}
	}

	/**
	 * Execute system requirements check (MediaWiki-style)
	 */
	private function executeSystemCheck($step)
	{
		$checks = $step["checks"] ?? [];
		$results = [];

		foreach ($checks as $check) {
			switch ($check) {
				case "php_version":
					$required = $step["php_version"] ?? "7.4.0";
					if (version_compare(PHP_VERSION, $required, "<")) {
						throw new \Exception(
							"PHP {$required} required, found " . PHP_VERSION
						);
					}
					$results[] = "PHP version OK: " . PHP_VERSION;
					break;

				case "extensions":
					$extensions = $step["extensions"] ?? ["mbstring", "xml", "json"];
					foreach ($extensions as $ext) {
						if (!extension_loaded($ext)) {
							throw new \Exception("PHP extension required: {$ext}");
						}
						$results[] = "Extension OK: {$ext}";
					}
					break;

				case "permissions":
					$paths = $step["paths"] ?? ["storage", "bootstrap/cache"];
					foreach ($paths as $path) {
						$fullPath = base_path($path);
						if (!is_writable($fullPath)) {
							throw new \Exception("Directory not writable: {$path}");
						}
						$results[] = "Permissions OK: {$path}";
					}
					break;
			}
		}

		return ["message" => implode(", ", $results)];
	}

	/**
	 * Execute database optimization (MediaWiki-style)
	 */
	private function executeDatabaseOptimization($step, $moduleName)
	{
		$operations = $step["operations"] ?? ["analyze", "optimize"];

		foreach ($operations as $operation) {
			switch ($operation) {
				case "analyze":
					// Analyze table for better query performance
					$tables = $this->getModuleTables($moduleName);
					foreach ($tables as $table) {
						DB::statement("ANALYZE TABLE {$table}");
					}
					break;

				case "optimize":
					// Optimize tables to reclaim unused space
					$tables = $this->getModuleTables($moduleName);
					foreach ($tables as $table) {
						DB::statement("OPTIMIZE TABLE {$table}");
					}
					break;

				case "reindex":
					// Rebuild indexes for better performance
					$tables = $this->getModuleTables($moduleName);
					foreach ($tables as $table) {
						DB::statement("ALTER TABLE {$table} ENGINE=InnoDB");
					}
					break;
			}
		}

		return [
			"message" =>
				"Database optimization completed: " . implode(", ", $operations),
		];
	}

	/**
	 * Get module-related database tables
	 */
	private function getModuleTables($moduleName)
	{
		$prefix = DB::getTablePrefix();
		$moduleSnake = Str::snake($moduleName);

		// Look for tables that might belong to this module
		$tables = DB::select("SHOW TABLES LIKE '{$prefix}{$moduleSnake}%'");

		return array_map(function ($table) {
			return array_values((array) $table)[0];
		}, $tables);
	}

	/**
	 * Execute cache optimization (WordPress-style)
	 */
	private function executeCacheOptimization($step)
	{
		$operations = $step["operations"] ?? ["clear", "warm"];

		foreach ($operations as $operation) {
			switch ($operation) {
				case "clear":
					Cache::flush();
					Artisan::call("cache:clear");
					Artisan::call("config:clear");
					Artisan::call("route:clear");
					Artisan::call("view:clear");
					break;

				case "warm":
					// Warm up commonly used caches
					Artisan::call("config:cache");
					Artisan::call("route:cache");
					break;

				case "opcache":
					if (function_exists("opcache_reset")) {
						opcache_reset();
					}
					break;
			}
		}

		return [
			"message" =>
				"Cache optimization completed: " . implode(", ", $operations),
		];
	}

	/**
	 * Execute WordPress-style hook system
	 */
	private function executeHook($step, $moduleName)
	{
		$hookName = $step["hook"] ?? "";
		$hookData = $step["data"] ?? [];

		// Simulate WordPress-style hook system
		$this->triggerHook("before_{$hookName}", $moduleName, $hookData);
		$result = $this->triggerHook($hookName, $moduleName, $hookData);
		$this->triggerHook("after_{$hookName}", $moduleName, $hookData);

		return ["message" => "Hook executed: {$hookName}", "result" => $result];
	}

	/**
	 * Trigger a hook (WordPress-style)
	 */
	private function triggerHook($hookName, $moduleName, $data = [])
	{
		// In a real implementation, you'd have a hook registry
		$hookResults = [];

		// Simulate hook execution
		Log::info("Triggering hook: {$hookName} for module {$moduleName}");

		return $hookResults;
	}

	/**
	 * Execute event-based step
	 */
	private function executeEvent($step, $moduleName)
	{
		$eventClass = $step["event"] ?? "";
		$eventData = $step["data"] ?? [];

		if (class_exists($eventClass)) {
			event(new $eventClass($moduleName, $eventData));
			return ["message" => "Event dispatched: {$eventClass}"];
		}

		throw new \Exception("Event class not found: {$eventClass}");
	}

	// ... Keep other existing methods (executeMigration, executeSeeder, etc.)

	/**
	 * Log step execution
	 */
	private function logStep($type, $message)
	{
		return [
			"timestamp" => now()->toISOString(),
			"type" => $type,
			"message" => $message,
		];
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
