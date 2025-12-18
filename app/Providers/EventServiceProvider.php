<?php

namespace Modules\Core\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Modules\Core\Events\ModuleInstalled;
use Modules\Core\Events\ModuleInstallationFailed;
use Modules\Core\Listeners\ProcessModuleInstallation;
use Modules\Core\Listeners\HandleInstallationFailure;
use Modules\Core\Listeners\MigrateGuestThemeToUser;
use Modules\Core\Listeners\CleanupGuestPreferences;
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
		Login::class => [MigrateGuestThemeToUser::class],
		Logout::class => [CleanupGuestPreferences::class],
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
