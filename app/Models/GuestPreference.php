<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;

class GuestPreference extends Model
{
	protected $table = "guest_preferences";

	protected $fillable = ["session_id", "key", "value", "expires_at"];

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
	 * Scope untuk session tertentu
	 */
	public function scopeForSession($query, $sessionId)
	{
		return $query->where("session_id", $sessionId);
	}

	/**
	 * Scope untuk preference yang belum expired
	 */
	public function scopeNotExpired($query)
	{
		return $query->where(function ($q) {
			$q->whereNull("expires_at")->orWhere("expires_at", ">", now());
		});
	}
}
