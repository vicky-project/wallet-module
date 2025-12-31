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
			$table->string("name"); // BCA, Mandiri, Cash, OVO, dll
			$table->string("type");
			$table->string("account_number")->nullable();
			$table->string("bank_name")->nullable();
			$table->bigInteger("initial_balance")->default(0);
			$table->bigInteger("current_balance")->default(0);
			$table->boolean("is_default")->default(false);
			$table->timestamps();
			$table->softDeletes();
		});

		Schema::create("transactions", function (Blueprint $table) {
			$table->id();
			$table
				->foreignId("user_id")
				->constrained()
				->onDelete("cascade");
			$table
				->foreignId("category_id")
				->constrained()
				->onDelete("cascade");
			$table->string("title"); // Deskripsi transaksi
			$table->text("description")->nullable();
			$table->bigInteger("amount");
			$table->string("type");
			$table->date("transaction_date");
			$table->string("payment_method");
			$table->string("reference_number")->nullable(); // no referensi
			$table->boolean("is_recurring")->default(false);
			$table->string("recurring_period")->nullable(); // daily, weekly, monthly
			$table->date("recurring_end_date")->nullable();
			$table->boolean("is_verified")->default(true);
			$table->timestamps();

			$table->index(["user_id", "transaction_date", "type"]);
			$table->index(["transaction_date", "type"]);
		});

		Schema::create("categories", function (Blueprint $table) {
			$table->id();
			$table
				->foreignId("user_id")
				->constrained()
				->onDelete("cascade");
			$table->string("name"); // Makanan, Transportasi, Gaji, dll
			$table->string("type"); // pemasukan/pengeluaran
			$table->string("icon")->default("bi-wallet");
			$table->decimal("budget_limit", 15, 2)->nullable(); // batas anggaran
			$table->boolean("is_active")->default(true);
			$table->timestamps();
			$table->softDeletes();

			$table->index(["user_id", "type"]);
		});

		Schema::create("budgets", function (Blueprint $table) {
			$table->id();
			$table
				->foreignId("user_id")
				->constrained()
				->onDelete("cascade");
			$table
				->foreignId("category_id")
				->constrained()
				->onDelete("cascade");
			$table->bigInteger("amount");
			$table->integer("month"); // 1-12
			$table->integer("year");
			$table->bigInteger("spent")->default(0);
			$table->decimal("remaining", 15, 2)->virtualAs("amount - spent");
			$table->decimal("percentage", 5, 2)->virtualAs("(spent / amount) * 100");
			$table->timestamps();
			$table->softDeletes();

			$table->unique(["user_id", "category_id", "month", "year"]);
			$table->index(["user_id", "month", "year"]);
		});

		Schema::create("saving_goals", function (Blueprint $table) {
			$table->id();
			$table
				->foreignId("user_id")
				->constrained()
				->onDelete("cascade");
			$table->string("name"); // Liburan, Beli Mobil, DP Rumah
			$table->bigInteger("target_amount");
			$table->bigInteger("current_amount")->default(0);
			$table->date("target_date");
			$table->string("priority")->default("medium");
			$table->boolean("is_completed")->default(false);
			$table->date("completed_at")->nullable();
			$table->timestamps();
			$table->softDeletes();
		});

		Schema::create("transfers", function (Blueprint $table) {
			$table->id();
			$table
				->foreignId("user_id")
				->constrained()
				->onDelete("cascade");
			$table
				->foreignId("from_account_id")
				->constrained("accounts")
				->onDelete("cascade");
			$table
				->foreignId("to_account_id")
				->constrained("accounts")
				->onDelete("cascade");
			$table->bigInteger("amount");
			$table->date("transfer_date");
			$table->text("description")->nullable();
			$table->bigInteger("fee")->default(0);
			$table->timestamps();
			$table->softDeletes();
		});
	}

	public function down()
	{
		Schema::dropIfExists("transfers");
		Schema::dropIfExists("saving_goals");
		Schema::dropIfExists("budgets");
		Schema::dropIfExists("categories");
		Schema::dropIfExists("transactions");
		Schema::dropIfExists("accounts");
	}
};
