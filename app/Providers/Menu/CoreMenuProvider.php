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
		"icon" => "bi bi-server",
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
				"title" => "Server",
				"icon" => "bi bi-server",
				"type" => "dropdown",
				"order" => 100,
				"children" => [
					$this->item([
						"title" => "Modules",
						"icon" => "bi bi-puzzle",
						"route" => "cores.modules.index",
						"order" => 1,
						"permission" => Permissions::VIEW_MODULES,
					]),
					$this->item([
						"title" => "Systems",
						"icon" => "bi bi-cpu",
						"route" => "cores.systems.index",
						"order" => 2,
						"permission" => Permissions::VIEW_SYSTEMS,
					]),
				],
			]),
		];
	}
}
