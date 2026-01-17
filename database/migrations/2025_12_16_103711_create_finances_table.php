<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up()
	{
		// 1. Accounts
		Schema::create("accounts", function (Blueprint $table) {
			$table->id();
			$table
				->foreignId("user_id")
				->constrained()
				->onDelete("cascade");
			$table->string("name");
			$table->string("type")->default("cash");
			$table->bigInteger("balance")->default(0);
			$table->bigInteger("initial_balance")->default(0);
			$table->string("currency", 3)->default("IDR");
			$table->string("account_number")->nullable();
			$table->string("bank_name")->nullable();
			$table->string("color", 7)->default("#3490dc");
			$table->string("icon")->default("bi-wallet");
			$table->boolean("is_active")->default(true);
			$table->boolean("is_default")->default(false);
			$table->text("notes")->nullable();
			$table->timestamps();
			$table->softDeletes();

			$table->unique(["user_id", "is_active", "created_at"]);
		});

		// 2. Categories
		Schema::create("categories", function (Blueprint $table) {
			$table->id();
			$table
				->foreignId("user_id")
				->nullable()
				->constrained()
				->onDelete("cascade");
			$table->string("name");
			$table->string("type")->default("expense");
			$table->string("icon")->default("category");
			$table->text("description")->nullable();
			$table->boolean("is_active")->default(true);
			$table->boolean("is_budgetable")->default(false);
			$table->string("slug")->unique();
			$table->timestamps();
			$table->softDeletes();

			$table->index(["user_id", "type", "is_active"]);
		});

		// 3. Recurring Transactions
		Schema::create("recurring_transactions", function (Blueprint $table) {
			$table->id();
			$table
				->foreignId("user_id")
				->constrained()
				->onDelete("cascade");
			$table
				->foreignId("account_id")
				->constrained("accounts")
				->onDelete("cascade");
			$table
				->foreignId("category_id")
				->constrained("categories")
				->onDelete("restrict");
			$table->string("type");
			$table->bigInteger("amount");
			$table->string("description");
			$table->string("frequency");
			$table->integer("interval")->default(1);
			$table->date("start_date");
			$table->date("end_date")->nullable();
			$table->integer("day_of_month")->nullable();
			$table->integer("day_of_week")->nullable();
			$table->json("custom_schedule")->nullable();
			$table->boolean("is_active")->default(true);
			$table->integer("remaining_occurrences")->nullable();
			$table->date("last_processed")->nullable();
			$table->timestamps();
			$table->softDeletes();
		});

		// 4. Transactions
		Schema::create("transactions", function (Blueprint $table) {
			$table->id();
			$table->uuid("uuid")->unique();
			$table
				->foreignId("user_id")
				->constrained()
				->onDelete("cascade");
			$table
				->foreignId("account_id")
				->constrained("accounts")
				->onDelete("cascade");
			$table
				->foreignId("to_account_id")
				->nullable()
				->constrained("accounts")
				->onDelete("set null");
			$table
				->foreignId("category_id")
				->constrained("categories")
				->onDelete("restrict");
			$table->string("type");
			$table->bigInteger("amount");
			$table->bigInteger("original_amount")->nullable(); // For multi-currency
			$table->string("original_currency", 3)->nullable();
			$table->string("description");
			$table->text("notes")->nullable();
			$table->dateTime("transaction_date");
			$table->boolean("is_recurring")->default(false);
			$table
				->foreignId("recurring_template_id")
				->nullable()
				->constrained("recurring_transactions")
				->onDelete("set null");
			$table->string("reference_number")->nullable();
			$table->string("payment_method")->nullable();
			$table->json("metadata")->nullable();
			$table->timestamps();
			$table->softDeletes();

			$table->index(["user_id", "type", "transaction_date"]);
			$table->index(["user_id", "account_id", "transaction_date"]);
			$table->index(["user_id", "category_id", "transaction_date"]);
		});

		// 5. Budgets
		Schema::create("budgets", function (Blueprint $table) {
			$table->id();
			$table
				->foreignId("user_id")
				->constrained()
				->onDelete("cascade");
			$table
				->foreignId("category_id")
				->constrained("categories")
				->onDelete("cascade");
			$table->string("name")->nullable(); // Optional custom name
			$table->string("period_type")->default("monthly");
			$table->integer("period_value"); // Month number, week number, etc.
			$table->integer("year");
			$table->date("start_date");
			$table->date("end_date");
			$table->bigInteger("amount");
			$table->bigInteger("spent")->default(0);
			$table->boolean("rollover_unused")->default(false);
			$table->bigInteger("rollover_limit")->nullable();
			$table->boolean("is_active")->default(true);
			$table->json("settings")->nullable();
			$table->timestamps();
			$table->softDeletes();

			$table->unique(
				["user_id", "category_id", "period_type", "period_value", "year"],
				"budget_period_unique"
			);
			$table->index(["user_id", "is_active", "start_date", "end_date"]);
		});

		// 6. Budget Accounts
		Schema::create("budget_accounts", function (Blueprint $table) {
			$table->id();
			$table
				->foreignId("budget_id")
				->constrained()
				->onDelete("cascade");
			$table
				->foreignId("account_id")
				->constrained("accounts")
				->onDelete("cascade");
			$table->timestamps();

			$table->unique(["budget_id", "account_id"]);
		});

		// 7. Saving Goals
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

		// 8. Tags
		Schema::create("tags", function (Blueprint $table) {
			$table->id();
			$table
				->foreignId("user_id")
				->constrained()
				->onDelete("cascade");
			$table->string("name");
			$table->string("color", 7)->default("#6c757d");
			$table->string("icon")->nullable();
			$table->timestamps();
			$table->softDeletes();

			$table->unique(["user_id", "name"]);
		});

		// 9. Transaction Tags (Pivot)
		Schema::create("transaction_tags", function (Blueprint $table) {
			$table->id();
			$table
				->foreignId("transaction_id")
				->constrained()
				->onDelete("cascade");
			$table
				->foreignId("tag_id")
				->constrained()
				->onDelete("cascade");
			$table->timestamps();

			$table->unique(["transaction_id", "tag_id"]);
		});
	}

	public function down()
	{
		Schema::dropIfExists("transaction_tags");
		Schema::dropIfExists("tags");
		Schema::dropIfExists("saving_goals");
		Schema::dropIfExists("budget_accounts");
		Schema::dropIfExists("budgets");
		Schema::dropIfExists("transactions");
		Schema::dropIfExists("recurring_transactions");
		Schema::dropIfExists("categories");
		Schema::dropIfExists("accounts");
	}
};
