<?php

namespace Modules\Core\Constants;

class Permissions
{
	// Module permissions
	const VIEW_MODULES = "cores.modules.view";
	const MANAGE_MODULES = "cores.modules.manage";

	// Systems Permissions
	const VIEW_SYSTEMS = "cores.modules.view";

	public static function all(): array
	{
		return [
			// Modules
			self::VIEW_MODULES => "View all modules",
			self::MANAGE_MODULES => "Manage modules",

			// Systems
			self::VIEW_SYSTEMS => "View systems",
		];
	}
}
