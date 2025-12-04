<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Number;
use Nwidart\Modules\Facades\Module;
use Linfo\Linfo;

class ServerMonitorService
{
	protected $cpuHistory = [];
	protected $memoryHistory = [];
	protected $maxHistory = 30;
	protected $linfo;

	public function __construct()
	{
		$linfo = new Linfo(config("core.monitors"));
		$linfo->scan();
		$this->linfo = $linfo->getParser();
	}

	public function getStaticData()
	{
		return [
			"kernel" => $this->linfo->getKernel(),
			"hostname" => $this->linfo->getHostName(),
			"cpu" => $this->linfo->getCPU(),
			"model" => $this->linfo->getModel(),
			"distro" => $this->linfo->getDistro(),
			"raid" => $this->linfo->getRAID(),
			"devs" => $this->linfo->getDevs(),
		];
	}

	public function getDynamicData()
	{
		return [
			"ram" => $this->linfo->getRam(),
			"cpu_usage" => $this->linfo->getCPUUsage(),
			"cpu" => $this->linfo->getCPU(),
			"load" => $this->linfo->getLoad(),
			"temps" => $this->linfo->getTemps(),
			"network" => $this->linfo->getNet(),
			"process_stats" => $this->linfo->getProcessStats(),
			"hd" => $this->linfo->getHD(),
			"mounts" => $this->linfo->getMounts(),
			"uptime" => $this->linfo->getUpTime(),
			"services" => $this->linfo->getServices(),
		];
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
