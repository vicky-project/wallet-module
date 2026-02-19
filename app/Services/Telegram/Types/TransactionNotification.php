<?php
namespace Modules\Wallet\Services\Telegram\Types;

use Modules\Wallet\Models\Transaction;
use Modules\Wallet\Enums\TransactionType;
use Modules\Wallet\Services\Telegram\Builders\MessageBuilder;
use Modules\Wallet\Services\Telegram\Builders\KeyboardBuilder;
use Modules\Wallet\Services\Telegram\Support\RateLimiter;
use Modules\Wallet\Services\Telegram\Support\TelegramApi;
use Illuminate\Support\Number;

class TransactionNotification extends BaseNotification
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
		return "new_transaction";
	}

	public function buildMessage(): string
	{
		$transaction = $this->data["transaction"] ?? null;

		if (!$transaction instanceof Transaction) {
			return "📝 *Transaksi Baru*";
		}

		return $this->messageBuilder->buildTransactionDetailMessage($transaction);
	}

	public function buildKeyboard(): ?array
	{
		$transaction = $this->data["transaction"] ?? null;
		if (!$transaction) {
			return null;
		}

		return $this->keyboardBuilder->buildTransactionDetailKeyboard($transaction);
	}
}
