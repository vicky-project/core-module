<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
	protected $table = "user_preferences";

	protected $fillable = ["user_id", "key", "value"];

	protected $casts = [
		"value" => "json",
	];

	/**
	 * Scope untuk mendapatkan preference berdasarkan key
	 */
	public function scopeForKey($query, $key)
	{
		return $query->where("key", $key);
	}

	/**
	 * Scope untuk user tertentu
	 */
	public function scopeForUser($query, $userId)
	{
		return $query->where("user_id", $userId);
	}
}
