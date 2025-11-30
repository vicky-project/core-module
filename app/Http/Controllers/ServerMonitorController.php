<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Http\StreamedEvent;
use Modules\Core\Services\ServerMonitorService;
use Modules\Core\Constants\Permissions;

class ServerMonitorController extends Controller
{
	protected $serverMonitor;

	public function __construct(ServerMonitorService $serverMonitor)
	{
		$this->serverMonitor = $serverMonitor;

		$this->middleware(["permission:" . Permissions::VIEW_SYSTEMS])->only([
			"index",
		]);
	}

	public function index(Request $request)
	{
		return view("core::server.index");
	}

	public function streamMetrics(Request $request)
	{
		$updateInterval = max(1, min(10, (int) $request->get("interval", 5)));
		return response()->eventStream(
			function () use ($request, $updateInterval) {
				$lastMetrics = [];
				$heartbeatCount = 0;
				$isPageVisible = true;

				try {
					yield $this->formatEvent("connected", [
						"message" => "Server connected.",
						"interval" => $updateInterval,
						"timestamp" => now()->toISOString(),
					]);

					while (true) {
						if (connection_aborted()) {
							logger()->info("SSE client disconnect from metrics stream");
							break;
						}

						if (!$isPageVisible) {
							sleep($updateInterval);
							continue;
						}

						$metrics = $this->serverMonitor->getServerStatus();
						if ($this->metricsChanged($lastMetrics, $metrics)) {
							yield $this->formatEvent("metrics", $metrics);
							$lastMetrics = $metrics;
						}

						if ($heartbeatCount % (30 / $updateInterval) === 0) {
							yield $this->formatEvent("heartbeat", [
								"timestamp" => now()->toISOString(),
								"count" => $heartbeatCount,
							]);
						}

						$heartbeatCount++;
						sleep($updateInterval);
					}
				} catch (\Exception $e) {
					logger()->error("SSE metrices stream error: " . $e->getMessage());
					yield $this->formatEvent("error", [
						"message" => "Metrics stream error",
						"error" => $e->getMessage(),
					]);
				}
			},
			[
				"Content-Type" => "text/event-stream",
				"Cache-Control" => "no-cache",
				"Connection" => "keep-alive",
				"X-Accel-Buffering" => "no",
				"Access-Control-Allow-Origin" => "*",
				"Access-Control-Allow-Headers" => "Cache-Control",
			]
		);
	}

	/**
	 * Stream server health status
	 */
	public function streamHealth(Request $request)
	{
		return response()->eventStream(
			function () use ($request) {
				$lastHealth = [];

				try {
					while (true) {
						if (connection_aborted()) {
							break;
						}

						$health = $this->serverMonitor->isServerHealthy();

						if ($this->healtChanged($lastHealth, $health)) {
							yield $this->formatEvent("health", $health);
							$lastHealth = $health;
						}

						// Wait 5 seconds before next health check
						sleep(5);
					}
				} catch (\Exception $e) {
					logger()->error("SSE health stream error: " . $e->getMessage());
					yield $this->formatEvent("error", [
						"message" => "Health stream error",
						"error" => $e->getMessage(),
					]);
				}
			},
			[
				"Content-Type" => "text/event-stream",
				"Cache-Control" => "no-cache",
				"Connection" => "keep-alive",
				"X-Accel-Buffering" => "no",
			]
		);
	}

	private function metricsChanged($old, $new): bool
	{
		if (empty($old)) {
			return true;
		}

		$thresholds = [
			"resources.memory_per centage" => 1,
			"resources.disk_usage.percentage" => 1,
			"resources.cpu_usage.load_1min" => 0.5,
		];

		foreach ($thresholds as $path => $threshold) {
			$oldValue = $this->getNestedValue($old, $path);
			$newValue = $this->getNestedValue($new, $path);

			if ($this->isSignificantChange($oldValue, $newValue, $threshold)) {
				return true;
			}
		}

		return false;
	}

	private function healtChanged($old, $new): bool
	{
		if (empty($old)) {
			return true;
		}

		return $old["healhty"] !== $new["healhty"] ||
			json_encode($old["checks"]) !== json_encode($new["checks"]);
	}

	private function getNestedValue($array, $path)
	{
		$keys = explode(".", $path);
		$value = $array;

		foreach ($keys as $key) {
			if (!isset($value[$key])) {
				return null;
			}

			$value = $value[$key];
		}

		return $value;
	}

	private function isSignificantChange($old, $nee, $threshold): bool
	{
		if ($old === null || $new === null) {
			return true;
		}

		if (is_numeric($old) && is_numeric($new)) {
			$change = abs($new - $old);
			return $change > $threshold;
		}

		return $old !== $new;
	}

	private function formatEvent($message, $data)
	{
		return new StreamedEvent(event: $message, data: json_encode($data));
	}
}
