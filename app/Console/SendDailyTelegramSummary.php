<?php
namespace Modules\Wallet\Console;

use Illuminate\Console\Command;
use App\Models\User;
use Modules\Wallet\Enums\TransactionType;
use Modules\Wallet\Services\Telegram\TelegramNotificationService;
use Modules\Wallet\Repositories\TransactionRepository;
use Carbon\Carbon;

class SendDailyTelegramSummary extends Command
{
	protected $signature = 'telegram:daily-summary 
                          {--test : Send to specific user for testing} 
                          {--user-id= : User ID for testing}';

	protected $description = "Send daily summary to Telegram users";

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
		if ($this->option("test")) {
			return $this->sendTestSummary();
		}

		$users = User::where("telegram_notifications", true)
			->whereNotNull("telegram_chat_id")
			->whereHas("accounts")
			->get();

		$today = Carbon::today();
		$yesterday = Carbon::yesterday();

		foreach ($users as $user) {
			try {
				// Check if user wants daily summary
				$settings = $this->notificationService->getUserSettings($user);
				if (!$settings["daily_summary"]) {
					continue;
				}

				// Get today's transactions
				$transactions = $this->transactionRepository->getDailySummary(
					$user->id,
					$today->format("Y-m-d"),
					$today->format("Y-m-d")
				);

				// Calculate totals
				$income = 0;
				$expense = 0;
				$transactionCount = 0;
				$categoryTotals = [];

				foreach ($transactions as $transaction) {
					$transactionCount++;
					$amount = $transaction->amount;

					if ($transaction->type === TransactionType::INCOME) {
						$income += $amount;
					} elseif ($transaction->type === TransactionType::EXPENSE) {
						$expense += $amount;

						// Track categories
						if (!isset($categoryTotals[$transaction->category_id])) {
							$categoryTotals[$transaction->category_id] = [
								"name" => $transaction->category->name,
								"amount" => 0,
							];
						}
						$categoryTotals[$transaction->category_id]["amount"] += $amount;
					}
				}

				// Get top 3 categories
				arsort($categoryTotals);
				$topCategories = array_slice($categoryTotals, 0, 3);

				// Get budget alerts
				$budgetAlerts = $this->getBudgetAlerts($user);

				$summary = [
					"date" => $today->format("Y-m-d"),
					"income" => $income,
					"expense" => $expense,
					"count" => $transactionCount,
					"top_categories" => array_values($topCategories),
					"budget_alerts" => $budgetAlerts,
				];

				// Send notification
				$this->notificationService->notifyDailySummary($user, $summary);

				$this->info("Sent daily summary to user: {$user->email}");
			} catch (\Exception $e) {
				$this->error("Failed to send to user {$user->id}: " . $e->getMessage());
				continue;
			}
		}

		$this->info("Daily summaries sent successfully.");
	}

	private function getBudgetAlerts(User $user): array
	{
		$alerts = [];
		$budgets = $user
			->budgets()
			->where("is_active", true)
			->whereDate("start_date", "<=", Carbon::today())
			->whereDate("end_date", ">=", Carbon::today())
			->with("category")
			->get();

		foreach ($budgets as $budget) {
			$usage =
				($budget->spent->getAmount()->toInt() /
					$budget->amount->getAmount()->toInt()) *
				100;
			if ($usage >= 80) {
				$alerts[] = [
					"category" => $budget->category->name,
					"percentage" => round($usage, 1),
				];
			}
		}

		return $alerts;
	}

	private function sendTestSummary()
	{
		$userId = $this->option("user-id");

		if (!$userId) {
			$this->error("Please provide user ID with --user-id option");
			return 1;
		}

		$user = User::find($userId);

		if (!$user) {
			$this->error("User not found");
			return 1;
		}

		$summary = [
			"income" => 1500000,
			"expense" => 750000,
			"count" => 8,
			"top_categories" => [
				["name" => "Makanan", "amount" => 350000],
				["name" => "Transportasi", "amount" => 200000],
				["name" => "Hiburan", "amount" => 150000],
			],
			"budget_alerts" => [
				["category" => "Makanan", "percentage" => 85],
				["category" => "Belanja", "percentage" => 92],
			],
		];

		$this->notificationService->notifyDailySummary($user, $summary);
		$this->info("Test summary sent to user: {$user->email}");

		return 0;
	}
}
