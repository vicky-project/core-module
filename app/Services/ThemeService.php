<?php
// Modules/Core/Services/ThemeService.php

namespace Modules\Core\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Modules\Core\Models\UserPreference;
use Modules\Core\Models\GuestPreference;

class ThemeService
{
	const THEMES = [
		"light" => [
			"name" => "Light",
			"icon" => "fas fa-sun",
			"class" => "",
			"description" => "Default light theme",
		],
		"dark" => [
			"name" => "Dark",
			"icon" => "fas fa-moon",
			"class" => "dark-mode",
			"description" => "Dark mode for reduced eye strain",
		],
	];

	const DEFAULT_THEME = "light";
	const THEME_KEY = "theme";
	const GUEST_PREFERENCE_TTL = 30 * 24 * 60; // 30 hari dalam menit

	/**
	 * Get current theme dengan prioritas:
	 * 1. Database (User Preference)
	 * 2. Database (Guest Preference)
	 * 3. Session
	 * 4. Cookie
	 * 5. Default
	 */
	public function getCurrentTheme()
	{
		$theme = null;

		// Priority 1: User Preference Database (jika user login)
		if (Auth::check()) {
			$theme = $this->getThemeFromUserPreference(Auth::id());
			if ($theme) {
				// Sync dengan session dan cookie untuk consistency
				$this->syncThemeToSessionAndCookie($theme);
				return $theme;
			}
		}

		// Priority 2: Guest Preference Database (jika ada session)
		$sessionId = Session::getId();
		if ($sessionId) {
			$theme = $this->getThemeFromGuestPreference($sessionId);
			if ($theme) {
				$this->syncThemeToSessionAndCookie($theme);
				return $theme;
			}
		}

		// Priority 3: Session
		$theme = Session::get(self::THEME_KEY);
		if ($theme && $this->isValidTheme($theme)) {
			// Simpan ke guest preference jika ada session
			if ($sessionId) {
				$this->saveThemeToGuestPreference($sessionId, $theme);
			}
			return $theme;
		}

		// Priority 4: Cookie
		if (Cookie::has(self::THEME_KEY)) {
			$theme = Cookie::get(self::THEME_KEY);
			if ($theme && $this->isValidTheme($theme)) {
				// Simpan ke session dan guest preference
				Session::put(self::THEME_KEY, $theme);
				if ($sessionId) {
					$this->saveThemeToGuestPreference($sessionId, $theme);
				}
				return $theme;
			}
		}

		// Priority 5: Default
		$defaultTheme = config("core.default_theme", self::DEFAULT_THEME);
		return $this->isValidTheme($defaultTheme)
			? $defaultTheme
			: self::DEFAULT_THEME;
	}

	/**
	 * Set theme dan simpan ke semua storage berdasarkan kondisi
	 */
	public function setTheme($theme)
	{
		if (!$this->isValidTheme($theme)) {
			$theme = self::DEFAULT_THEME;
		}

		// 1. Simpan ke Session
		Session::put(self::THEME_KEY, $theme);

		// 2. Simpan ke Cookie (30 hari)
		Cookie::queue(self::THEME_KEY, $theme, 30 * 24 * 60);

		// 3. Simpan ke Database berdasarkan kondisi
		$sessionId = Session::getId();

		if (Auth::check()) {
			// Simpan ke User Preference
			$this->saveThemeToUserPreference(Auth::id(), $theme);

			// Hapus guest preference untuk user ini jika ada
			if ($sessionId) {
				GuestPreference::forSession($sessionId)
					->forKey(self::THEME_KEY)
					->delete();
			}
		} elseif ($sessionId) {
			// Simpan ke Guest Preference
			$this->saveThemeToGuestPreference($sessionId, $theme);
		}

		return $theme;
	}

	/**
	 * Get theme dari user preference database
	 */
	private function getThemeFromUserPreference($userId)
	{
		try {
			$preference = UserPreference::forUser($userId)
				->forKey(self::THEME_KEY)
				->first();

			if ($preference && $preference->value) {
				$themeData = $preference->value;
				$theme = is_array($themeData)
					? $themeData["theme"] ?? null
					: $themeData;

				if ($theme && $this->isValidTheme($theme)) {
					return $theme;
				}
			}
		} catch (\Exception $e) {
			// Jika tabel belum ada atau error, return null
			logger()->error(
				"Error getting theme from user preference: " . $e->getMessage()
			);
		}

		return null;
	}

	/**
	 * Get theme dari guest preference database
	 */
	private function getThemeFromGuestPreference($sessionId)
	{
		try {
			$preference = GuestPreference::forSession($sessionId)
				->forKey(self::THEME_KEY)
				->notExpired()
				->first();

			if ($preference && $preference->value) {
				$themeData = $preference->value;
				$theme = is_array($themeData)
					? $themeData["theme"] ?? null
					: $themeData;

				if ($theme && $this->isValidTheme($theme)) {
					return $theme;
				}
			}
		} catch (\Exception $e) {
			// Jika tabel belum ada atau error, return null
			logger()->error(
				"Error getting theme from guest preference: " . $e->getMessage()
			);
		}

		return null;
	}

	/**
	 * Save theme ke user preference
	 */
	private function saveThemeToUserPreference($userId, $theme)
	{
		try {
			UserPreference::updateOrCreate(
				[
					"user_id" => $userId,
					"key" => self::THEME_KEY,
				],
				[
					"value" => ["theme" => $theme, "updated_at" => now()->toISOString()],
				]
			);
		} catch (\Exception $e) {
			logger()->error(
				"Error saving theme to user preference: " . $e->getMessage()
			);
		}
	}

	/**
	 * Save theme ke guest preference
	 */
	private function saveThemeToGuestPreference($sessionId, $theme)
	{
		try {
			GuestPreference::updateOrCreate(
				[
					"session_id" => $sessionId,
					"key" => self::THEME_KEY,
				],
				[
					"value" => ["theme" => $theme, "updated_at" => now()->toISOString()],
					"expires_at" => now()->addMinutes(self::GUEST_PREFERENCE_TTL),
				]
			);
		} catch (\Exception $e) {
			logger()->error(
				"Error saving theme to guest preference: " . $e->getMessage()
			);
		}
	}

	/**
	 * Sync theme ke session dan cookie
	 */
	private function syncThemeToSessionAndCookie($theme)
	{
		if (
			!Session::has(self::THEME_KEY) ||
			Session::get(self::THEME_KEY) !== $theme
		) {
			Session::put(self::THEME_KEY, $theme);
		}

		if (
			!Cookie::has(self::THEME_KEY) ||
			Cookie::get(self::THEME_KEY) !== $theme
		) {
			Cookie::queue(self::THEME_KEY, $theme, 30 * 24 * 60);
		}
	}

	/**
	 * Check if theme is valid
	 */
	public function isValidTheme($theme)
	{
		return array_key_exists($theme, self::THEMES);
	}

	/**
	 * Get all available themes
	 */
	public function getAvailableThemes()
	{
		return self::THEMES;
	}

	/**
	 * Get theme config
	 */
	public function getThemeConfig($theme = null)
	{
		$theme = $theme ?: $this->getCurrentTheme();
		return self::THEMES[$theme] ?? self::THEMES[self::DEFAULT_THEME];
	}

	/**
	 * Get body class for current theme
	 */
	public function getBodyClass()
	{
		$theme = $this->getCurrentTheme();
		return self::THEMES[$theme]["class"] ?? "";
	}

	/**
	 * Reset theme to default
	 */
	public function resetTheme()
	{
		$defaultTheme = config("core.default_theme", self::DEFAULT_THEME);

		if (Auth::check()) {
			// Hapus dari user preference
			UserPreference::forUser(Auth::id())
				->forKey(self::THEME_KEY)
				->delete();
		}

		// Hapus dari session dan cookie
		Session::forget(self::THEME_KEY);
		Cookie::queue(Cookie::forget(self::THEME_KEY));

		return $defaultTheme;
	}

	/**
	 * Migrate guest theme to user theme (saat login)
	 */
	public function migrateGuestToUser($userId, $sessionId)
	{
		try {
			// Cari theme dari guest preference
			$guestPreference = GuestPreference::forSession($sessionId)
				->forKey(self::THEME_KEY)
				->first();

			if ($guestPreference && $guestPreference->value) {
				$themeData = $guestPreference->value;
				$theme = is_array($themeData)
					? $themeData["theme"] ?? null
					: $themeData;

				if ($theme && $this->isValidTheme($theme)) {
					// Simpan ke user preference
					$this->saveThemeToUserPreference($userId, $theme);

					// Hapus guest preference
					$guestPreference->delete();

					return $theme;
				}
			}
		} catch (\Exception $e) {
			logger()->error(
				"Error migrating guest theme to user: " . $e->getMessage()
			);
		}

		return null;
	}

	/**
	 * Get theme storage information (untuk debugging/info)
	 */
	public function getThemeStorageInfo()
	{
		$info = [
			"current_theme" => $this->getCurrentTheme(),
			"sources" => [],
		];

		// Check User Preference
		if (Auth::check()) {
			$userTheme = $this->getThemeFromUserPreference(Auth::id());
			$info["sources"]["database_user"] = $userTheme ?: "not_set";
		}

		// Check Guest Preference
		$sessionId = Session::getId();
		if ($sessionId) {
			$guestTheme = $this->getThemeFromGuestPreference($sessionId);
			$info["sources"]["database_guest"] = $guestTheme ?: "not_set";
		}

		// Check Session
		$info["sources"]["session"] = Session::get(self::THEME_KEY, "not_set");

		// Check Cookie
		$info["sources"]["cookie"] = Cookie::has(self::THEME_KEY)
			? Cookie::get(self::THEME_KEY)
			: "not_set";

		return $info;
	}
}
