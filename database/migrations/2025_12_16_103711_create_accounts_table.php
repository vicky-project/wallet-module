<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up()
	{
		Schema::create("accounts", function (Blueprint $table) {
			$table->id();
			$table
				->foreignId("user_id")
				->constrained()
				->onDelete("cascade");
			$table->string("name");
			$table->string("type")->default("general");
			$table->text("description")->nullable();
			$table->string("currency", 3)->default("IDR");
			$table->boolean("is_active")->default(true);
			$table->timestamps();

			$table->index("user_id");
		});
	}

	public function down()
	{
		Schema::dropIfExists("accounts");
	}
};
