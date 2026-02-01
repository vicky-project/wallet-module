<?php
namespace Modules\Wallet\Listeners;

use Modules\Wallet\Events\TelegramNotificationEvent;
use Modules\Wallet\Services\TelegramNotificationService;

class SendTelegramNotificationListener
{
	protected $notificationService;

	public function __construct(TelegramNotificationService $notificationService)
	{
		$this->notificationService = $notificationService;
	}

	public function handle(TelegramNotificationEvent $event)
	{
		$user = $event->user;

		if (!$user->hasLinkedTelegram() || !$user->telegram_notifications) {
			return;
		}

		switch ($event->type) {
			case "new_transaction":
				$this->notificationService->notifyNewTransaction(
					$user,
					$event->data["transaction"]
				);
				break;

			case "budget_warning":
				$this->notificationService->notifyBudgetWarning(
					$user,
					$event->data["budget"],
					$event->data["percentage"]
				);
				break;

			case "budget_exceeded":
				$this->notificationService->notifyBudgetExceeded(
					$user,
					$event->data["budget"]
				);
				break;

			case "low_balance":
				$this->notificationService->notifyLowBalance(
					$user,
					$event->data["account"],
					$event->data["threshold"]
				);
				break;

			case "daily_summary":
				$this->notificationService->notifyDailySummary(
					$user,
					$event->data["summary"]
				);
				break;

			case "weekly_summary":
				$this->notificationService->notifyWeeklySummary(
					$user,
					$event->data["summary"]
				);
				break;

			case "bill_reminder":
				$this->notificationService->notifyBillReminder(
					$user,
					$event->data["bills"]
				);
				break;

			case "savings_milestone":
				$this->notificationService->notifySavingsMilestone(
					$user,
					$event->data["account"],
					$event->data["milestone"]
				);
				break;

			case "income_achievement":
				$this->notificationService->notifyIncomeAchievement(
					$user,
					$event->data["achievement"]
				);
				break;

			case "custom":
				$this->notificationService->sendCustomNotification(
					$user,
					$event->data["title"],
					$event->data["content"],
					$event->data["notification_type"] ?? "info"
				);
				break;
		}
	}
}
