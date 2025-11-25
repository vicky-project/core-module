<?php

namespace Modules\Core\Providers;

use Modules\MenuManagement\Interfaces\MenuProviderInterface;
use Modules\Core\Constants\Permissions;

class MenuProvider implements MenuProviderInterface
{
	/**
	 * Get Menu for LogManagement Module.
	 */
	public static function getMenus(): array
	{
		return [
			[
				"id" => "core",
				"name" => "Core",
				"order" => -1,
				"icon" => "memory",
				"role" => "super-admin",
				"type" => "group",
				"children" => [
					[
						"id" => "modules",
						"name" => "Modules",
						"order" => 10,
						"icon" => "input-power",
						"route" => "cores.modules.index",
						"role" => "super-admin",
						"permission" => Permissions::VIEW_MODULES,
					],
				],
			],
		];
	}
}
