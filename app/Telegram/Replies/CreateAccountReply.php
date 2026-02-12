<?php
namespace Modules\Wallet\Telegram\Replies;

use Illuminate\Support\Facades\Log;
use Modules\Telegram\Services\Handlers\Replies\BaseReplyHandler;
use Modules\Telegram\Services\Support\TelegramApi;

class CreateAccountReply extends BaseReplyHandler
{
	public function __construct(TelegramApi $telegramApi)
	{
		parent::__construct($telegramApi);
	}

	public function getModuleName(): string
	{
		return "wallet";
	}

	public function getEntity(): string
	{
		return "account";
	}

	public function getAction(): string
	{
		return "create";
	}

	public function handle(
		array $context,
		string $replyText,
		int $chatId,
		int $replyToMessageId
	): array {
		Log::debug("Reply message from: " . $chatId, [
			"chat_id" => $chatId,
			"reply_to" => $replyToMessageId,
			"text" => $replyText,
			"context" => $context,
		]);
	}
}
