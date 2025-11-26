<?php

namespace Modules\Core\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ModuleInstalled
{
	use Dispatchable, InteractsWithSockets, SerializesModels;

	public $packageName;
	public $version;
	public $moduleName;

	/**
	 * Create a new event instance.
	 */
	public function __construct($packageName, $version, $moduleName)
	{
		$this->packageName = $packageName;
		$this->version = $version;
		$this->moduleName = $moduleName;
	}

	/**
	 * Get the channels the event should be broadcast on.
	 */
	public function broadcastOn(): array
	{
		return [new PrivateChannel("channel-module-install")];
	}
}
