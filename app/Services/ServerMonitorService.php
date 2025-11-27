<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Nwidart\Modules\Facades\Module;
use Illuminate\Support\Number;

class ServerMonitorService
{
	public function getServerStatus()
	{
		return [
			"timestamp" => now()->toISOString(),
			"system" => $this->getSystemInfo(),
			"resources" => $this->getResourceUsage(),
			"application" => $this->getApplicationStatus(),
			"modules" => $this->getModulesStatus(),
			"database" => $this->getDatabaseStatus(),
			"queue" => $this->getQueueStatus(),
		];
	}

	protected function getSystemInfo()
	{
		return [
			"php_version" => PHP_VERSION,
			"laravel_version" => app()->version(),
			"server_software" => $_SERVER["SERVER_SOFTWARE"] ?? "Unknown",
			"hostname" => gethostname(),
			"os" => php_uname("s") . " " . php_uname("r"),
			"timezone" => config("app.timezone"),
			"environment" => config("app.env"),
		];
	}

	protected function getResourceUsage()
	{
		return [
			"memory_usage" => Number::fileSize(memory_get_usage(true)),
			"memory_peak" => Number::fileSize(memory_get_peak_usage(true)),
			"memory_limit" => ini_get("memory_limit"),
			"cpu_usage" => $this->getCpuUsage(),
			"disk_usage" => $this->getDiskUsage(),
			"disk_free" => Number::fileSize(disk_free_space(base_path())),
			"disk_total" => Number::fileSize(disk_total_space(base_path())),
		];
	}

	protected function getApplicationStatus()
	{
		return [
			"maintenance_mode" => app()->isDownForMaintenance(),
			"cache_driver" => config("cache.default"),
			"queue_driver" => config("queue.default"),
			"session_driver" => config("session.driver"),
			"uptime" => $this->getUptime(),
			"active_connections" => $this->getActiveConnections(),
		];
	}

	protected function getModulesStatus()
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

	protected function getDatabaseStatus()
	{
		try {
			$connection = config("database.default");
			$pdo = \DB::connection()->getPdo();

			return [
				"status" => "connected",
				"connection" => $connection,
				"version" => $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION),
				"database" => config("database.connections.{$connection}.database"),
			];
		} catch (\Exception $e) {
			return [
				"status" => "disconnected",
				"error" => $e->getMessage(),
			];
		}
	}

	protected function getQueueStatus()
	{
		try {
			$queue = app("queue");
			$size = 0;

			// Cek jumlah job dalam queue (tergantung driver)
			if (config("queue.default") === "database") {
				$size = \DB::table("jobs")->count();
			}

			return [
				"driver" => config("queue.default"),
				"size" => $size,
				"status" => "running",
			];
		} catch (\Exception $e) {
			return [
				"status" => "error",
				"error" => $e->getMessage(),
			];
		}
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

	protected function getDiskUsage()
	{
		$total = disk_total_space(base_path());
		$free = disk_free_space(base_path());
		$used = $total - $free;

		return [
			"used" => Number::fileSize($used),
			"free" => Number::fileSize($free),
			"total" => Number::fileSize($total),
			"percentage" => round(($used / $total) * 100, 2),
		];
	}

	protected function getUptime()
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

	protected function getActiveConnections()
	{
		// Untuk web server, kita bisa estimasi dari active processes
		try {
			if (function_exists("exec")) {
				$result = exec("ps aux | grep php | grep -v grep | wc -l");
				return intval(trim($result)) ?: 0;
			}
		} catch (\Exception $e) {
			Log::warning("Cannot get active connections: " . $e->getMessage());
		}

		return 0;
	}

	protected function formatUptime($seconds)
	{
		$days = floor($seconds / 86400);
		$hours = floor(($seconds % 86400) / 3600);
		$minutes = floor(($seconds % 3600) / 60);

		return "{$days}d {$hours}h {$minutes}m";
	}

	/**
	 * Get real-time metrics for monitoring
	 */
	public function getRealtimeMetrics()
	{
		$metrics = $this->getServerStatus();

		// Cache metrics untuk akses cepat
		Cache::put("server_metrics", $metrics, 30);

		return $metrics;
	}

	/**
	 * Check if server is healthy
	 */
	public function isServerHealthy()
	{
		$status = $this->getServerStatus();

		$checks = [
			"database" => $status["database"]["status"] === "connected",
			"disk_space" => $status["resources"]["disk_usage"]["percentage"] < 90,
			"memory" =>
				$this->convertToBytes($status["resources"]["memory_usage"]) <
				$this->convertToBytes($status["resources"]["memory_limit"]) * 0.9,
		];

		return [
			"healthy" => !in_array(false, $checks, true),
			"checks" => $checks,
			"details" => $status,
		];
	}

	protected function convertToBytes($memory)
	{
		$unit = strtolower(substr($memory, -1));
		$value = (int) substr($memory, 0, -1);

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
}
