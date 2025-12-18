<?php
// Modules/Core/Listeners/MigrateGuestThemeToUser.php

namespace Modules\Core\Listeners;

use Illuminate\Auth\Events\Login;
use Modules\Core\Services\ThemeService;

class MigrateGuestThemeToUser
{
	protected $themeService;

	public function __construct(ThemeService $themeService)
	{
		$this->themeService = $themeService;
	}

	public function handle(Login $event)
	{
		$sessionId = session()->getId();

		if ($sessionId) {
			// Migrasi theme dari guest ke user
			$this->themeService->migrateGuestToUser($event->user->id, $sessionId);
		}
	}
}
