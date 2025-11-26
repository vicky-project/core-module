<?php

namespace Modules\Core\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ModuleInstallationFailed
{
	use Dispatchable, InteractsWithSockets, SerializesModels;

	public $packageName;
	public $version;
	public $error;
	public $backupPath;

	/**
	 * Create a new event instance.
	 */
	public function __construct(
		$packageName,
		$version,
		$error,
		$backupPath = null
	) {
		$this->packageName = $packageName;
		$this->version = $version;
		$this->error = $error;
		$this->backupPath = $backupPath;
	}

	/**
	 * Get the channels the event should be broadcast on.
	 */
	public function broadcastOn(): array
	{
		return [new PrivateChannel("channel-module-install")];
	}
}
