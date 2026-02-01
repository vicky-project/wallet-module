<?php
namespace Modules\Wallet\Console;

use Illuminate\Console\Command;
use App\Models\User;
use Modules\Wallet\Enums\TransactionType;
use Modules\Wallet\Services\TelegramNotificationService;
use Modules\Wallet\Repositories\TransactionRepository;
use Carbon\Carbon;

class SendWeeklyTelegramReport extends Command
{
	protected $signature = "telegram:weekly-report";
	protected $description = "Send weekly financial report to Telegram users";

	protected $notificationService;
	protected $transactionRepository;

	public function __construct(
		TelegramNotificationService $notificationService,
		TransactionRepository $transactionRepository
	) {
		parent::__construct();
		$this->notificationService = $notificationService;
		$this->transactionRepository = $transactionRepository;
	}

	public function handle()
	{
		$users = User::where("telegram_notifications", true)
			->whereNotNull("telegram_chat_id")
			->get();

		$weekStart = Carbon::now()->startOfWeek();
		$weekEnd = Carbon::now()->endOfWeek();
		$lastWeekStart = Carbon::now()
			->subWeek()
			->startOfWeek();
		$lastWeekEnd = Carbon::now()
			->subWeek()
			->endOfWeek();

		foreach ($users as $user) {
			try {
				// Check if user wants weekly summary
				$settings = $this->notificationService->getUserSettings($user);
				if (!$settings["weekly_summary"]) {
					continue;
				}

				// Current week transactions
				$currentWeek = $this->getWeekSummary($user, $weekStart, $weekEnd);

				// Last week transactions for comparison
				$lastWeek = $this->getWeekSummary($user, $lastWeekStart, $lastWeekEnd);

				// Calculate growth
				$incomeGrowth =
					$lastWeek["income"] > 0
						? (($currentWeek["income"] - $lastWeek["income"]) /
								$lastWeek["income"]) *
							100
						: 0;

				$expenseGrowth =
					$lastWeek["expense"] > 0
						? (($currentWeek["expense"] - $lastWeek["expense"]) /
								$lastWeek["expense"]) *
							100
						: 0;

				$summary = [
					"income" => $currentWeek["income"],
					"expense" => $currentWeek["expense"],
					"previous_week" => $lastWeek["expense"],
					"categories" => $currentWeek["categories"],
					"growth" => $incomeGrowth,
				];

				$this->notificationService->notifyWeeklySummary($user, $summary);

				$this->info("Sent weekly report to user: {$user->email}");
			} catch (\Exception $e) {
				$this->error("Failed to send to user {$user->id}: " . $e->getMessage());
				continue;
			}
		}

		$this->info("Weekly reports sent successfully.");
	}

	private function getWeekSummary(User $user, Carbon $start, Carbon $end): array
	{
		$transactions = $user
			->transactions()
			->whereBetween("transaction_date", [$start, $end])
			->with("category")
			->get();

		$income = 0;
		$expense = 0;
		$categories = [];

		foreach ($transactions as $transaction) {
			$amount = $transaction->amount;

			if ($transaction->type === TransactionType::INCOME) {
				$income += $amount;
			} elseif ($transaction->type === TransactionType::EXPENSE) {
				$expense += $amount;

				$categoryId = $transaction->category_id;
				if (!isset($categories[$categoryId])) {
					$categories[$categoryId] = [
						"name" => $transaction->category->name,
						"amount" => 0,
					];
				}
				$categories[$categoryId]["amount"] += $amount;
			}
		}

		// Sort categories by amount
		usort($categories, function ($a, $b) {
			return $b["amount"] <=> $a["amount"];
		});

		return [
			"income" => $income,
			"expense" => $expense,
			"categories" => array_slice($categories, 0, 5), // Top 5 categories
		];
	}
}
