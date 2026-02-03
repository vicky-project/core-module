<?php

namespace Modules\Core\Traits;

trait HasModuleTrait
{
	/**
	 * Boot trait - dipanggil secara static
	 */
	public static function bootHasModuleTrait()
	{
		// Method ini static, gunakan approach static

		// 1. Extend fillable melalui static property
		static::extendStaticFillable([
			"telegram_id",
			"telegram_username",
			"auth_date",
		]);

		// 2. Extend casts melalui static property
		static::extendStaticCasts([
			"auth_date" => "timestamp",
		]);

		// 3. Setup event untuk instance-based extensions
		static::booted(function ($model) {
			// Instance-based extensions bisa dilakukan di sini
			static::applyInstanceExtensions($model);
		});
	}

	/**
	 * Extend fillable secara static
	 */
	protected static function extendStaticFillable(array $attributes)
	{
		if (!isset(static::$fillable)) {
			static::$fillable = [];
		}

		static::$fillable = array_unique(
			array_merge(static::$fillable, $attributes)
		);
	}

	/**
	 * Extend casts secara static
	 */
	protected static function extendStaticCasts(array $casts)
	{
		if (!isset(static::$casts)) {
			static::$casts = [];
		}

		static::$casts = array_merge(static::$casts, $casts);
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
	public static function addModuleFillable(array $attributes)
	{
		static::extendStaticFillable($attributes);
	}

	/**
	 * Helper method untuk module lain menambah casts
	 */
	public static function addModuleCasts(array $casts)
	{
		static::extendStaticCasts($casts);
	}
}
