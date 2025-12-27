<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up()
	{
		Schema::create("wallets", function (Blueprint $table) {
			$table->id();
			$table
				->foreignId("user_id")
				->constrained()
				->onDelete("cascade");
			$table->string("name");
			$table->string("wallet_code")->unique();
			$table->string("type")->default("cash"); // cash, bank, digital, investment
			$table->bigInteger("balance")->default(0);
			$table->bigInteger("initial_balance")->default(0);
			$table->string("currency");
			$table->boolean("is_default")->default(false);
			$table->text("description")->nullable();
			$table->json("metadata")->nullable();
			$table->timestamps();
			$table->softDeletes();

			$table->index(["user_id", "is_default"]);
			$table->index("wallet_code");
		});

		Schema::create("transactions", function (Blueprint $table) {
			$table->id();
			$table->uuid("transaction_code")->unique();
			$table
				->foreignId("wallet_id")
				->constrained()
				->onDelete("cascade");
			$table
				->foreignId("user_id")
				->constrained()
				->onDelete("cascade");

			// Transaction detail
			$table->string("type"); // deposit, withdraw, transaction
			$table->string("category")->nullable();
			$table->bigInteger("amount");
			$table->string("currency");

			// Transfer details (if type is transfer)
			$table
				->foreignId("to_wallet_id")
				->nullable()
				->constrained("wallets");

			// Transaction info
			$table->date("transaction_date");
			$table->string("payment_method")->nullable();
			$table->string("reference_number")->nullable();

			// Notes
			$table->text("description")->nullable();
			$table->text("note")->nullable();
			$table->json("attachments")->nullable(); // JSOJ array of file path

			// Reconciliation
			$table->boolean("is_reconciled")->default(false);
			$table->dateTime("reconciled_at")->nullable();
			$table
				->foreignId("reconciled_by")
				->nullable()
				->constrained("users");
			$table->json("meta")->nullable();

			$table->timestamps();
			$table->softDeletes();

			$table->index(["wallet_id", "transaction_date"]);
			$table->index(["user_id", "type"]);
			$table->index(["transaction_code"]);
			$table->index(["type", "transaction_date"]);
		});

		Schema::create("categories", function (Blueprint $table) {
			$table->id();
			$table
				->foreignId("user_id")
				->nullable()
				->constrained()
				->onDelete("cascade");
			$table->string("name");
			$table->string("type"); // income, expense, transfer
			$table->string("icon")->nullable();
			$table->integer("order")->default(0);
			$table->boolean("is_active")->default(true);
			$table->timestamps();
			$table->softDeletes();

			$table->index(["user_id", "type", "is_active"]);
		});
	}

	public function down()
	{
		Schema::dropIfExists("categories");
		Schema::dropIfExists("transactions");
		Schema::dropIfExists("wallets");
	}
};
