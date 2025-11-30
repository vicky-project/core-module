<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Number;
use Nwidart\Modules\Facades\Module;

class ServerMonitorService
{
	protected $cpuHistory = [];
	protected $memoryHistory = [];
	protected $maxHistory = 30;

	public function getServerStatus()
	{
		$memoryUsage = memory_get_usage(true);
		$memoryLimit = $this->convertToBytes(ini_get("memory_limit"));
		$diskTotal = disk_total_space(base_path());
		$diskFree = disk_free_space(base_path());
		$diskUsed = $diskTotal - $diskFree;

		$metrics = [
			"timestamp" => now()->toISOString(),
			"system" => $this->getSystemInfo(),
			"resources" => [
				"memory_usage" => Number::fileSize($memoryUsage),
				"memory_limit" => $memoryLimit,
				"memory_percentage" =>
					$memoryLimit > 0 ? round(($memoryUsage / $memoryLimit) * 100, 2) : 0,
				"cpu_usage" => $this->getCpuUsage(),
				"disk_usage" => [
					"used" => Number::fileSize($diskUsed),
					"free" => Number::fileSize($diskFree),
					"total" => Number::fileSize($diskTotal),
					"percentage" =>
						$diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100, 2) : 0,
				],
			],
			"application" => $this->getApplicationStatus(),
			"database" => $this->getDatabaseStatus(),
		];

		// Cache metrics untuk akses cepat
		Cache::put("server_metrics", $metrics, now()->addMinutes(1));

		return $metrics;
	}

	protected function updateHistory($metrics)
	{
		// Update CPU history
		$cpuLoad = $metrics["resources"]["cpu_usage"]["load_1min"] ?? 0;
		$this->cpuHistory[] = $cpuLoad;
		if (count($this->cpuHistory) > $this->maxHistory) {
			array_shift($this->cpuHistory);
		}

		// Update memory history
		$memoryPercent = $metrics["resources"]["memory_percentage"] ?? 0;
		$this->memoryHistory[] = round($memoryPercent, 2);
		if (count($this->memoryHistory) > $this->maxHistory) {
			array_shift($this->memoryHistory);
		}

		// Cache history
		Cache::put(
			"server_monitor_history",
			[
				"cpu" => $this->cpuHistory,
				"memory" => $this->memoryHistory,
			],
			60
		);
	}

	protected function getCpuHistory()
	{
		if (empty($this->cpuHistory)) {
			$cached = Cache::get("server_monitor_history");
			$this->cpuHistory = $cached["cpu"] ?? array_fill(0, 10, 0);
		}
		return $this->cpuHistory;
	}

	protected function getMemoryHistory()
	{
		if (empty($this->memoryHistory)) {
			$cached = Cache::get("server_monitor_history");
			$this->memoryHistory = $cached["memory"] ?? array_fill(0, 10, 0);
		}
		return $this->memoryHistory;
	}

	public function getSystemInfo()
	{
		return [
			"php_version" => PHP_VERSION,
			"laravel_version" => app()->version(),
			"server_software" => $_SERVER["SERVER_SOFTWARE"] ?? "Unknown",
			"hostname" => gethostname(),
			"os" => php_uname("s") . " " . php_uname("r"),
			"timezone" => config("app.timezone"),
			"environment" => config("app.env"),
			"uptime" => $this->getSystemUptime(),
		];
	}

	protected function getSystemUptime()
	{
		try {
			if (file_exists("/proc/uptime")) {
				$uptime = file_get_contents("/proc/uptime");
				$uptime = floatval(explode(" ", $uptime)[0]);
				return $this->formatUptime($uptime);
			}
		} catch (\Exception $e) {
			Log::warning("Cannot get system uptime: " . $e->getMessage());
		}

		return "Unknown";
	}

	protected function getCpuUsage()
	{
		try {
			if (function_exists("sys_getloadavg")) {
				$load = sys_getloadavg();
				return [
					"load_1min" => round($load[0], 2),
					"load_5min" => round($load[1], 2),
					"load_15min" => round($load[2], 2),
				];
			}
		} catch (\Exception $e) {
			Log::warning("Cannot get CPU usage: " . $e->getMessage());
		}

		return ["load_1min" => 0, "load_5min" => 0, "load_15min" => 0];
	}

	public function getApplicationStatus()
	{
		return [
			"maintenance_mode" => app()->isDownForMaintenance(),
			"cache_driver" => config("cache.default"),
			"queue_driver" => config("queue.default"),
			"session_driver" => config("session.driver"),
			"uptime" => $this->getApplicationUptime(),
		];
	}

	protected function getApplicationUptime()
	{
		$startTime = defined("LARAVEL_START")
			? LARAVEL_START
			: config("app.start_time", microtime(true));
		$uptime = microtime(true) - $startTime;
		return $this->formatUptime($uptime);
	}

	protected function getActiveConnections()
	{
		try {
			if (function_exists("exec")) {
				$result = exec("ps aux | grep php | grep -v grep | wc -l");
				return intval(trim($result)) ?: 1;
			}
		} catch (\Exception $e) {
			Log::warning("Cannot get active connections: " . $e->getMessage());
		}

		return 1;
	}

	public function getModulesStatus()
	{
		$modules = Module::all();
		$status = [];

		foreach ($modules as $module) {
			$status[] = [
				"name" => $module->getName(),
				"enabled" => $module->isEnabled(),
				"version" => $module->get("version", "1.0.0"),
				"path" => $module->getPath(),
			];
		}

		return $status;
	}

	public function getDatabaseStatus()
	{
		try {
			$connection = config("database.default");
			$pdo = \DB::connection()->getPdo();

			// Get database statistics
			$tables = \DB::select(
				"SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = ?",
				[config("database.connections.{$connection}.database")]
			);

			return [
				"status" => "connected",
				"connection" => $connection,
				"version" => $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION),
				"database" => config("database.connections.{$connection}.database"),
				"tables" => $tables[0]->count ?? 0,
			];
		} catch (\Exception $e) {
			return [
				"status" => "disconnected",
				"error" => $e->getMessage(),
			];
		}
	}

	public function getQueueStatus()
	{
		try {
			$size = 0;
			$failed = 0;

			switch (config("queue.default")) {
				case "database":
					$size = \DB::table("jobs")->count();
					$failed = \DB::table("failed_jobs")->count();
					break;
				case "redis":
					// Menggunakan Redis facade dengan benar
					if (
						config("queue.connections.redis.connection", "default") ===
						"default"
					) {
						$redis = Redis::connection();
					} else {
						$redis = Redis::connection(
							config("queue.connections.redis.connection")
						);
					}

					$queueName = config("queue.connections.redis.queue", "default");
					$size = $redis->lLen("queues:{$queueName}");

					// Get failed jobs count from Redis jika menggunakan failed_jobs database
					try {
						$failed = \DB::table("failed_jobs")->count();
					} catch (\Exception $e) {
						$failed = 0;
					}
					break;
				case "sync":
					$size = 0;
					$failed = 0;
					break;
				default:
					$size = 0;
					$failed = 0;
					break;
			}

			return [
				"driver" => config("queue.default"),
				"size" => $size,
				"failed" => $failed,
				"status" => "running",
			];
		} catch (\Exception $e) {
			Log::warning("Queue status check failed: " . $e->getMessage());
			return [
				"status" => "error",
				"error" => $e->getMessage(),
			];
		}
	}

	public function isServerHealthy()
	{
		$status = $this->getServerStatus();

		$checks = [
			"database" => $status["database"]["status"] === "connected",
			"disk_space" => $status["resources"]["disk_usage"]["percentage"] < 90,
			"memory" => $status["resources"]["memory_percentage"] < 90,
			"cpu_load" => ($status["resources"]["cpu_usage"]["load_1min"] ?? 0) < 10,
		];

		$failedChecks = array_filter($checks, function ($check) {
			return !$check;
		});

		return [
			"healthy" => empty($failedChecks),
			"checks" => $checks,
			"failed_checks" => array_keys($failedChecks),
			"details" => $status,
		];
	}

	protected function formatUptime($seconds)
	{
		$days = floor($seconds / 86400);
		$hours = floor(($seconds % 86400) / 3600);
		$minutes = floor(($seconds % 3600) / 60);

		if ($days > 0) {
			return "{$days}d {$hours}h {$minutes}m";
		} elseif ($hours > 0) {
			return "{$hours}h {$minutes}m";
		} else {
			return "{$minutes}m";
		}
	}

	protected function convertToBytes($memory)
	{
		if (is_numeric($memory)) {
			return $memory;
		}

		$units = [
			"B" => 1,
			"K" => 1024,
			"M" => 1048576,
			"G" => 1073741824,
			"T" => 1099511627776,
		];
		$unit = preg_replace("/[^BKMGT]/", "", strtoupper($memory));
		$value = floatval(preg_replace("/[^0-9.]/", "", $memory));

		return $value * ($units[$unit] ?? 1);
	}

	/**
	 * Get Redis memory usage information
	 */
	public function getRedisStatus()
	{
		try {
			$redis = Redis::connection();
			$info = $redis->info("memory");

			return [
				"status" => "connected",
				"used_memory" => Number::fileSize($info["used_memory"] ?? 0),
				"used_memory_human" => $info["used_memory_human"] ?? "0B",
				"used_memory_peak" => Number::fileSize($info["used_memory_peak"] ?? 0),
				"used_memory_peak_human" => $info["used_memory_peak_human"] ?? "0B",
				"keys" => $this->getRedisKeyCount(),
			];
		} catch (\Exception $e) {
			return [
				"status" => "disconnected",
				"error" => $e->getMessage(),
			];
		}
	}

	protected function getRedisKeyCount()
	{
		try {
			$redis = Redis::connection();
			// Hati-hati dengan perintah KEYS di production, gunakan SCAN untuk environment production
			if (app()->environment("production")) {
				$iterator = null;
				$count = 0;
				do {
					list($iterator, $keys) = $redis->scan($iterator, ["count" => 1000]);
					$count += count($keys);
				} while ($iterator > 0);
				return $count;
			} else {
				return count($redis->keys("*"));
			}
		} catch (\Exception $e) {
			return 0;
		}
	}
}
