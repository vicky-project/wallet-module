<?php
namespace Modules\Wallet\Services\Telegram\Types;

use Modules\Wallet\Models\Budget;
use Modules\Wallet\Services\Telegram\Builders\MessageBuilder;
use Modules\Wallet\Services\Telegram\Builders\KeyboardBuilder;
use Modules\Wallet\Services\Telegram\Support\RateLimiter;
use Modules\Wallet\Services\Telegram\Support\TelegramApi;
use Illuminate\Support\Number;

class BudgetNotification extends BaseNotification
{
	protected MessageBuilder $messageBuilder;
	protected KeyboardBuilder $keyboardBuilder;

	public function __construct(
		TelegramApi $telegramApi,
		RateLimiter $rateLimiter,
		MessageBuilder $messageBuilder,
		KeyboardBuilder $keyboardBuilder
	) {
		parent::__construct($telegramApi, $rateLimiter);
		$this->messageBuilder = $messageBuilder;
		$this->keyboardBuilder = $keyboardBuilder;
	}

	public function getType(): string
	{
		return "budget_warning";
	}

	public function buildMessage(): string
	{
		$budget = $this->data["budget"] ?? null;
		$percentage = $this->data["percentage"] ?? 0;

		if (!$budget instanceof Budget) {
			return "⚠️ *Peringatan Budget*";
		}

		$remaining = Number::format($budget->remaining);
		$daysLeft = $budget->days_left;
		$dailyBudget = Number::format($budget->daily_budget);

		$message = "⚠️ *Peringatan Budget*\n\n";
		$message .= "📋 *Budget:* {$budget->name}\n";
		$message .= "📂 *Kategori:* {$budget->category->name}\n";
		$message .= "📊 *Penggunaan:* " . round($percentage) . "%\n";
		$message .= "💰 *Sisa:* Rp {$remaining}\n";
		$message .= "📅 *Hari Tersisa:* {$daysLeft}\n";
		$message .= "📆 *Budget Harian:* Rp {$dailyBudget}\n";

		if ($daysLeft > 0) {
			$suggestedDaily = floor($budget->remaining / $daysLeft);
			$message .=
				"\n💡 *Saran:* Batasi pengeluaran harian maksimal Rp " .
				Number::format($suggestedDaily);
		}

		return $message;
	}

	public function buildKeyboard(): ?array
	{
		$budget = $this->data["budget"] ?? null;
		if (!$budget) {
			return null;
		}

		return $this->keyboardBuilder->buildBudgetDetailKeyboard($budget);
	}
}
