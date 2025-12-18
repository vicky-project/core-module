<?php
// Modules/Core/Services/ThemeService.php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cookie;

class ThemeService
{
	const THEMES = [
		"light" => [
			"name" => "Light",
			"icon" => "fas fa-sun",
			"class" => "",
		],
		"dark" => [
			"name" => "Dark",
			"icon" => "fas fa-moon",
			"class" => "dark-mode",
		],
		"blue" => [
			"name" => "Blue",
			"icon" => "fas fa-palette",
			"class" => "blue-theme",
		],
		"green" => [
			"name" => "Green",
			"icon" => "fas fa-leaf",
			"class" => "green-theme",
		],
	];

	/**
	 * Get current theme
	 */
	public function getCurrentTheme()
	{
		// Priority: 1. Session, 2. Cookie, 3. Default
		$theme = Session::get("theme");

		if (!$theme && Cookie::has("theme")) {
			$theme = Cookie::get("theme");
			Session::put("theme", $theme);
		}

		return $theme ?: config("core.default_theme", "light");
	}

	/**
	 * Set theme
	 */
	public function setTheme($theme)
	{
		if (!array_key_exists($theme, self::THEMES)) {
			$theme = "light";
		}

		Session::put("theme", $theme);

		// Store in cookie for 30 days
		Cookie::queue("theme", $theme, 30 * 24 * 60);

		return $theme;
	}

	/**
	 * Get all available themes
	 */
	public function getAvailableThemes()
	{
		return self::THEMES;
	}

	/**
	 * Get theme class for body
	 */
	public function getBodyClass()
	{
		$theme = $this->getCurrentTheme();
		return self::THEMES[$theme]["class"] ?? "";
	}

	/**
	 * Get theme config
	 */
	public function getThemeConfig($theme = null)
	{
		$theme = $theme ?: $this->getCurrentTheme();
		return self::THEMES[$theme] ?? self::THEMES["light"];
	}
}
