<?php

namespace Modules\Core\Providers;

use Modules\Core\Events\ModuleInstalled;
use Modules\Core\Events\ModuleInstallationFailed;
use Modules\Core\Listeners\ProcessModuleInstallation;
use Modules\Core\Listeners\HandleInstallationFailure;
use Modules\Core\Listeners\SetCustomRedirectAfterLogin;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
	/**
	 * The event handler mappings for the application.
	 *
	 * @var array<string, array<int, string>>
	 */
	protected $listen = [
		ModuleInstalled::class => [ProcessModuleInstallation::class],
		ModuleInstallationFailed::class => [HandleInstallationFailure::class],
		Login::class => [SetCustomRedirectAfterLogin::class],
	];

	/**
	 * Indicates if events should be discovered.
	 *
	 * @var bool
	 */
	protected static $shouldDiscoverEvents = true;

	/**
	 * Configure the proper event listeners for email verification.
	 */
	protected function configureEmailVerification(): void
	{
	}
}
