<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up()
	{
		Schema::table("users", function (Blueprint $table) {
			$table
				->bigInteger("telegram_chat_id")
				->nullable()
				->unique();
			$table->string("telegram_username")->nullable();
			$table->string("telegram_verification_code")->nullable();
			$table->timestamp("telegram_code_expires_at")->nullable();
			$table->boolean("telegram_notifications")->default(true);
			$table->json("telegram_settings")->nullable();
		});
	}

	public function down()
	{
		Schema::table("users", function (Blueprint $table) {
			$table->dropColumn([
				"telegram_chat_id",
				"telegram_username",
				"telegram_verification_code",
				"telegram_code_expires_at",
				"telegram_notifications",
				"telegram_settings",
			]);
		});
	}
};
