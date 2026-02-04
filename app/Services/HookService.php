<?php
namespace Modules\Core\Services;

use Illuminate\Support\Facades\View;

class HookService
{
	private static $hooks = [];
	private static $rendered = [];

	/**
	 * Register a hook for specific view/position
	 */
	public static function add(
		string $position,
		callable $callback,
		int $priority = 10
	): void {
		if (!isset(self::$hooks[$position])) {
			self::$hooks[$position] = [];
		}

		self::$hooks[$position][] = [
			"callback" => $callback,
			"priority" => $priority,
		];

		// Sort by priority
		usort(self::$hooks[$position], function ($a, $b) {
			return $a["priority"] <=> $b["priority"];
		});
	}

	/**
	 * Render all hooks for a position
	 */
	public static function render(string $position, array $data = []): string
	{
		if (isset(self::$rendered[$position])) {
			return self::$rendered[$position];
		}

		$output = "";

		if (isset(self::$hooks[$position])) {
			foreach (self::$hooks[$position] as $hook) {
				$output .= call_user_func($hook["callback"], $data);
			}
		}

		self::$rendered[$position] = $output;
		return $output;
	}

	/**
	 * Check if position has hooks
	 */
	public static function has(string $position): bool
	{
		return !empty(self::$hooks[$position]);
	}

	/**
	 * Get all registered positions
	 */
	public static function positions(): array
	{
		return array_keys(self::$hooks);
	}
}
