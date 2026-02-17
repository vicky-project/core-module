<?php

namespace Modules\Core\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Session;

class SetCustomRedirectAfterLogin
{
	/**
	 * Handle the event.
	 *
	 * @param  \Illuminate\Auth\Events\Login  $event
	 * @return void
	 */
	public function handle(Login $event)
	{
		$user = $event->user;

		// Tentukan URL redirect berdasarkan role atau kondisi lain
		$redirectTo = "/";
		// Set session 'url.intended' agar redirect()->intended() mengarah ke sini
		Session::put("url.intended", $redirectTo);
	}
}
