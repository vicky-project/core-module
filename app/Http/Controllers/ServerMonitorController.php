<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Htt\Request;
use Illuminate\Routing\Controller;
use Illuminate\Http\StreamedEvent;
use Modules\Core\Services\ServerMonitorService;

class ServerMonitorController extends Controller
{
	protected $serverMonitor;

	public function __construct(ServerMonitorService $serverMonitor)
	{
		$this->serverMonitor = $serverMonitor;
	}

	public function index(Request $request)
	{
		return view("core::server.index");
	}

	public function streamMetrics(Request $request)
	{
		return response()
			->withHeaders([])
			->eventStream(function () use ($request) {
				try {
					$clientId = $request->getClientId() ?? uniqid();
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
	}

	private function sendEvent($message, $data)
	{
		yield new StreamedEvent(event: $message, data: $data);
	}
}
