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
		return response()->eventStream(function () use ($request) {
			try {
				$clientId = $request->client_id ?? uniqid();
				logger()->info("SSE connection started for client: {$clientId}");

				$this->sendEvent("connected", [
					"message" => "Server monitor connected.",
					"client_id" => $clientId,
					"timestamp" => now()->toISOString(),
				]);

				$heartbeatCount = 0;
				while (true) {
					if (connection_aborted()) {
						logger()->info("SSE connection closed by client: {$clientId}");
						break;
					}

					$metrics = $this->serverMonitor->getRealtimeMetrics();
					$this->sendEvent("metrics", $metrics);

					if ($heartbeatCount % 3 === 0) {
						$this->sendEvent("heartbeat", [
							"timestamp" => now()->toISOString(),
							"count" => $heartbeatCount,
						]);
					}

					$heartbeatCount++;

					if (ob_get_level() > 0) {
						ob_flush();
					}

					flush();

					sleep(3);
				}
			} catch (\Exception $e) {
				logger()->error("SSE stream error: " . $e->getMessage());
				$this->sendEvent("error", [
					"message" => "Monitor stream error",
					"error" => $e->getMessage(),
				]);
			}
		});
		//->withHeaders([
		//	"Content-Type" => "text/event-stream",
		//	"Cache-Control" => "no-cache",
		//	"Connection" => "keep-alive",
		//	"X-Accel-Buffering" => "no",
		//	"Access-Control-Allow-Origin" => "*",
		//	"Access-Control-Allow-Headers" => "Cache-Control",
		//]);
	}

	/**
	 * Stream server health status
	 */
	public function streamHealth(Request $request)
	{
		return response()->eventStream(function () use ($request) {
			try {
				while (true) {
					if (connection_aborted()) {
						break;
					}

					$health = $this->serverMonitor->isServerHealthy();
					$this->sendEvent("health", $health);

					// Wait 5 seconds before next health check
					sleep(5);
				}
			} catch (\Exception $e) {
				logger()->error("SSE health stream error: " . $e->getMessage());
			}
		});
		//->withHeaders([
		//	"Content-Type" => "text/event-stream",
		//	"Cache-Control" => "no-cache",
		//	"Connection" => "keep-alive",
		//	"X-Accel-Buffering" => "no",
		//]);
	}

	private function sendEvent($message, $data)
	{
		return yield new StreamedEvent(event: $message, data: $data);
	}
}
