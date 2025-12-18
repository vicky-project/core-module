<?php
// Modules/Core/Listeners/CleanupGuestPreferences.php

namespace Modules\Core\Listeners;

use Illuminate\Auth\Events\Logout;
use Modules\Core\Models\GuestPreference;

class CleanupGuestPreferences
{
	public function handle(Logout $event)
	{
		$sessionId = session()->getId();

		if ($sessionId) {
			// Hapus guest preferences untuk session ini
			GuestPreference::forSession($sessionId)->delete();
		}
	}
}
