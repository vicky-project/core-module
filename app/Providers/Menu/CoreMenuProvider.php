<?php
// Modules/Core/Providers/Menu/CoreMenuProvider.php

namespace Modules\Core\Providers\Menu;

use Modules\Core\Constants\Permissions;
use Modules\MenuManagement\Providers\BaseMenuProvider;

class CoreMenuProvider extends BaseMenuProvider
{
	protected array $config = [
		"group" => "server",
		"location" => "sidebar",
		"icon" => "fas fa-server",
		"order" => 1,
		"permission" => null,
	];

	public function __construct()
	{
		$moduleName = "Core";
		parent::__construct($moduleName);
	}

	/**
	 * Get all menus
	 */
	public function getMenus(): array
	{
		return [
			// System Server group
			$this->item([
				"title" => "System",
				"icon" => "fas fa-server",
				"type" => "dropdown",
				"order" => 100,
				"children" => [
					$this->item([
						"title" => "Modules",
						"icon" => "fas fa-puzzle-piece",
						"route" => "cores.modules.index",
						"order" => 1,
						"permission" => Permissions::VIEW_MODULES,
					]),
					$this->item([
						"title" => "Systems",
						"icon" => "fas fa-memory",
						"route" => "cores.systems.index",
						"order" => 2,
						"permission" => Permissions::VIEW_SYSTEMS,
					]),
				],
			]),
		];
	}
}
