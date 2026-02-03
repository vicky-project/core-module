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
			static::applyInstanceExtensions($model);
		});
	}

	/**
	 * Extend fillable secara static
	 */
	protected function extendStaticFillable(array $attributes)
	{
		\Log::info("Model " . get_class($this) . " adding attributes fillable", [
			"attributes" => $attributes,
		]);
		$this->mergeFillable($attributes);
	}

	/**
	 * Extend casts secara static
	 */
	protected function extendStaticCasts(array $casts)
	{
		\Log::info("Model " . get_class($this) . "adding casts", [
			"casts" => $casts,
		]);
		$this->mergeCasts($casts);
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
		$this->extendStaticFillable($attributes);
	}

	/**
	 * Helper method untuk module lain menambah casts
	 */
	public function addModuleCasts(array $casts)
	{
		$this->extendStaticCasts($casts);
	}
}
