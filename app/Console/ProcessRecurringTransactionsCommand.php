<?php

namespace Modules\Wallet\Console;

use Illuminate\Console\Command;
use Modules\Wallet\Services\RecurringTransactionService;
use Illuminate\Support\Facades\Log;

class ProcessRecurringTransactions extends Command
{
	protected $signature = 'app:process-recurring {--test : Test mode, don\'t actually create transactions} {--days= : Show upcoming transactions for X days}';

	protected $description = "Process due recurring transactions and create actual transactions";

	public function __construct(protected RecurringTransactionService $service)
	{
		parent::__construct();
	}

	public function handle(): int
	{
		if ($this->option("test") || $this->option("days")) {
			$days = $this->option("days") ?: 7;
			$this->info(
				"TEST MODE: Showing upcoming transactions for next {$days} days"
			);

			$upcoming = $this->service->getUpcomingTransactions($days);

			if (empty($upcoming)) {
				$this->info("No upcoming transactions found.");
				return Command::SUCCESS;
			}

			$this->table(
				["Date", "Description", "Amount", "Type", "Frequency", "Account"],
				array_map(function ($item) {
					return [
						$item["next_date"]->format("Y-m-d"),
						$item["description"],
						number_format($item["amount"], 2),
						$item["recurring"]->type,
						$item["frequency"],
						$item["account"],
					];
				}, $upcoming)
			);

			$this->info("\nTotal: " . count($upcoming) . " upcoming transactions");

			if ($this->option("test")) {
				return Command::SUCCESS;
			}
		}

		$this->info("Processing recurring transactions...");

		$results = $this->service->processDueRecurringTransactions();

		$this->info("✅ Processed: {$results["processed"]} transactions");
		$this->info("⏭️ Skipped: {$results["skipped"]} transactions");

		if (!empty($results["errors"])) {
			$this->error("❌ Errors: " . count($results["errors"]));
			foreach ($results["errors"] as $error) {
				$this->error(
					"  - Recurring #{$error["recurring_id"]}: {$error["error"]}"
				);
				Log::error("Recurring transaction error", $error);
			}
		}

		if (!empty($results["details"])) {
			$this->info("\n📋 Processed transactions:");
			foreach ($results["details"] as $detail) {
				$this->info(
					"  - #{$detail["transaction_id"]}: {$detail["description"]} ({$detail["amount"]})"
				);
			}
		}

		Log::info("Recurring transactions processed", $results);

		return Command::SUCCESS;
	}
}
