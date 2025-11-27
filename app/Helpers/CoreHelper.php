<?php

namespace Modules\Core\Helpers;

class CoreHelper
{
	public static function memoryToBytes($memory)
	{
		$unit = strtolower(substr($memory, -1));
		$value = (int) $memory;

		switch ($unit) {
			case "g":
				return $value * 1024 * 1024 * 1024;
			case "m":
				return $value * 1024 * 1024;
			case "k":
				return $value * 1024;
			default:
				return $value;
		}
	}
}
