<?php
// Modules/Core/Http/Controllers/ThemeController.php

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Services\ThemeService;

class ThemeController extends Controller
{
	protected $themeService;

	public function __construct(ThemeService $themeService)
	{
		$this->themeService = $themeService;
	}

	/**
	 * Show theme preferences page
	 */
	public function index()
	{
		$currentTheme = $this->themeService->getCurrentTheme();
		$themes = $this->themeService->getAvailableThemes();

		return view("core::theme.preferences", [
			"title" => "Theme Preferences",
			"currentTheme" => $currentTheme,
			"themes" => $themes,
			"breadcrumbs" => [
				["name" => "Dashboard", "url" => route("core.dashboard")],
				["name" => "Theme Preferences", "active" => true],
			],
		]);
	}

	/**
	 * Update theme preference
	 */
	public function update(Request $request)
	{
		$request->validate([
			"theme" =>
				"required|in:" .
				implode(",", array_keys($this->themeService->getAvailableThemes())),
		]);

		$theme = $this->themeService->setTheme($request->theme);

		return response()->json([
			"success" => true,
			"message" => "Theme updated successfully",
			"theme" => $theme,
			"theme_name" => $this->themeService->getThemeConfig($theme)["name"],
		]);
	}

	/**
	 * Toggle dark/light mode
	 */
	public function toggle(Request $request)
	{
		$currentTheme = $this->themeService->getCurrentTheme();

		if ($currentTheme === "dark") {
			$newTheme = "light";
		} else {
			$newTheme = "dark";
		}

		$theme = $this->themeService->setTheme($newTheme);

		return response()->json([
			"success" => true,
			"theme" => $theme,
			"is_dark" => $theme === "dark",
		]);
	}

	/**
	 * Get current theme info
	 */
	public function current(Request $request)
	{
		$theme = $this->themeService->getCurrentTheme();
		$config = $this->themeService->getThemeConfig($theme);

		return response()->json([
			"theme" => $theme,
			"name" => $config["name"],
			"icon" => $config["icon"],
			"class" => $config["class"],
		]);
	}
}
