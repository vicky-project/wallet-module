<?php
namespace Modules\Wallet\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use Modules\Wallet\Events\TelegramNotificationEvent;
use Modules\Wallet\Models\Budget;
use Carbon\Carbon;

class CheckBudgetWarnings extends Command
{
	protected $signature = "telegram:check-budgets";
	protected $description = "Check and send budget warnings";

	public function handle()
	{
		$users = User::where("telegram_notifications", true)
			->whereNotNull("telegram_chat_id")
			->get();

		$today = Carbon::today();

		foreach ($users as $user) {
			$budgets = $user
				->budgets()
				->where("is_active", true)
				->whereDate("start_date", "<=", $today)
				->whereDate("end_date", ">=", $today)
				->with("category")
				->get();

			foreach ($budgets as $budget) {
				$spent = $budget->spent->getAmount()->toInt();
				$amount = $budget->amount->getAmount()->toInt();

				if ($amount <= 0) {
					continue;
				}

				$usagePercentage = ($spent / $amount) * 100;

				// Budget exceeded
				if ($usagePercentage > 100) {
					event(
						new TelegramNotificationEvent($user, "budget_exceeded", [
							"budget" => $budget,
						])
					);
				}
				// Budget warning (80-100%)
				elseif ($usagePercentage >= 80 && $usagePercentage <= 100) {
					// Check if we already warned today
					$lastWarned = Cache::get("budget_warned:{$budget->id}");
					if (!$lastWarned || $lastWarned != $today->toDateString()) {
						event(
							new TelegramNotificationEvent($user, "budget_warning", [
								"budget" => $budget,
								"percentage" => $usagePercentage,
							])
						);

						// Mark as warned today
						Cache::put(
							"budget_warned:{$budget->id}",
							$today->toDateString(),
							86400
						);
					}
				}
			}
		}

		$this->info("Budget checks completed");
	}
}
