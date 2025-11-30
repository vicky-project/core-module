<?php

namespace Modules\Core\Listeners;

use Illuminate\Support\Facades\Log;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Core\Services\InstallationService;
use Modules\Core\Services\NotificationService;
use Modules\Core\Events\ModuleInstalled;
use Modules\Core\Events\ModuleInstallationFailed;

class ProcessModuleInstallation implements ShouldQueue
{
	use InteractsWithQueue;

	public $tries = 3;
	public $timeout = 300;

	protected $installationService;
	protected $notificationService;

	/**
	 * Create the event listener.
	 */
	public function __construct(
		InstallationService $installationService,
		NotificationService $notificationService
	) {
		$this->installationService = $installationService;
		$this->notificationService = $notificationService;
	}

	/**
	 * Handle the event.
	 */
	public function handle(ModuleInstalled $event): void
	{
		try {
			Log::info("Starting post-installation for module: {$event->moduleName}");

			// Process post-installation
			$this->installationService->processPostInstallation($event->moduleName);

			// Send notification
			$this->notificationService->sendInstallationSuccessNotification(
				$event->packageName,
				$event->version
			);

			Log::info("Post-installation completed for module: {$event->moduleName}");
		} catch (\Exception $e) {
			Log::error(
				"Post-installation failed for {$event->moduleName}: " . $e->getMessage()
			);

			// Trigger failed event
			event(
				new ModuleInstallationFailed(
					$event->packageName,
					$event->version,
					$e->getMessage()
				)
			);

			throw $e;
		}
	}

	public function failed(ModuleInstalled $event, $exception)
	{
		Log::error(
			"Post-installation job failed for {$event->moduleName}: " .
				$exception->getMessage()
		);

		event(
			new ModuleInstallationFailed(
				$event->packageName,
				$event->version,
				$exception->getMessage()
			)
		);
	}
}
