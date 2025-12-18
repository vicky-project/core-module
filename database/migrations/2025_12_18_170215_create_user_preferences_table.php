<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		Schema::create("user_preferences", function (Blueprint $table) {
			$table->id();
			$table->unsignedBigInteger("user_id")->nullable();
			$table->string("key");
			$table->text("value")->nullable();
			$table->timestamps();

			$table->unique(["user_id", "key"]);
			$table->index(["user_id", "key"]);

			// Foreign key akan ditambahkan nanti saat ada users table
			$table
				->foreign("user_id")
				->references("id")
				->on("users")
				->onDelete("cascade");
		});

		Schema::create("guest_preferences", function (Blueprint $table) {
			$table->id();
			$table->string("session_id");
			$table->string("key");
			$table->text("value")->nullable();
			$table->timestamp("expires_at")->nullable();
			$table->timestamps();

			$table->unique(["session_id", "key"]);
			$table->index(["session_id", "key"]);
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists("user_preferences");
		Schema::dropIfExists("guest_preferences");
	}
};
