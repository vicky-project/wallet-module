<?php
namespace Modules\Wallet\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use Modules\Wallet\Events\TelegramNotificationEvent;
use Modules\Wallet\Models\Account;

class CheckLowBalances extends Command
{
	protected $signature = 'telegram:check-balances 
                          {--threshold=100000 : Minimum balance threshold}';

	protected $description = "Check and notify low account balances";

	public function handle()
	{
		$threshold = (int) $this->option("threshold");

		$users = User::where("telegram_notifications", true)
			->whereNotNull("telegram_chat_id")
			->get();

		foreach ($users as $user) {
			$accounts = $user
				->accounts()
				->where("is_active", true)
				->get();

			foreach ($accounts as $account) {
				$balance = $account->balance->getAmount()->toInt();

				if ($balance < $threshold && $balance >= 0) {
					// Check if we already warned recently (last 24 hours)
					$lastWarned = Cache::get("balance_warned:{$account->id}");
					if (!$lastWarned) {
						event(
							new TelegramNotificationEvent($user, "low_balance", [
								"account" => $account,
								"threshold" => $threshold,
							])
						);

						// Mark as warned
						Cache::put(
							"balance_warned:{$account->id}",
							now()->toDateTimeString(),
							86400
						);
					}
				}
			}
		}

		$this->info("Balance checks completed");
	}
}
