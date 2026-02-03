<?php

namespace Modules\Core\Traits;

trait HasModuleTrait
{
	/**
	 * Boot trait - dipanggil secara static
	 */
	public static function bootHasModuleTrait()
	{
		static::booted(function ($model) {
			static::extendStaticFillable([
				"telegram_id",
				"telegram_username",
				"auth_date",
			]);

			static::extendStaticCasts([
				"auth_date" => "timestamp",
			]);

			static::applyInstanceExtensions($model);
		});
	}

	/**
	 * Extend fillable secara static
	 */
	protected static function extendStaticFillable(array $attributes)
	{
		if (!isset(self::$fillable)) {
			self::$fillable = [];
		}

		self::$fillable = array_unique(array_merge(self::$fillable, $attributes));
	}

	/**
	 * Extend casts secara static
	 */
	protected static function extendStaticCasts(array $casts)
	{
		if (!isset(self::$casts)) {
			self::$casts = [];
		}

		self::$casts = array_merge(self::$casts, $casts);
	}

	/**
	 * Apply instance-based extensions
	 */
	protected static function applyInstanceExtensions($model)
	{
		// Di sini kita bisa panggil instance methods
		// Contoh: tambahkan dynamic relations, scopes, dll

		// Event untuk memberi tahu module lain
		event(new \Modules\Core\Events\UserModelBooted($model));
	}

	/**
	 * Helper method untuk module lain menambah fillable
	 * Bisa dipanggil dari Service Provider module lain
	 */
	public function addModuleFillable(array $attributes)
	{
		self::extendStaticFillable($attributes);
	}

	/**
	 * Helper method untuk module lain menambah casts
	 */
	public function addModuleCasts(array $casts)
	{
		self::extendStaticCasts($casts);
	}
}
