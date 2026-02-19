<?php
namespace Modules\Wallet\Telegram\Replies;

use Illuminate\Support\Facades\Log;
use Modules\Telegram\Services\Handlers\Replies\BaseReplyHandler;
use Modules\Telegram\Services\Support\InlineKeyboardBuilder;
use Modules\Telegram\Services\Support\TelegramApi;
use Modules\Telegram\Services\Support\CacheReplyStateManager;

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
		?int $replyToMessageId
	): array {
		try {
			if (
				!$this->ensureReplyToMessageIdExists(
					$chatId,
					$replyToMessageId,
					$context
				)
			) {
				return ["status" => "missing_message_id"];
			}

			$oldState = CacheReplyStateManager::getReplyState(
				$chatId,
				$replyToMessageId
			);

			if (!$oldState) {
				$this->noticeUser($chatId, $context);
				return ["status" => "state_not_found"];
			}

			$accountName = trim($replyText);
			if (strlen($accountName) < 3) {
				$this->sendMessage(
					$chatId,
					"⚠️ Nama akun minimal 3 karakter. Silakan kirim ulang:"
				);
				return ["keep_reply_state" => true];
			}

			$inlineKeyboard = app(InlineKeyboardBuilder::class);
			$inlineKeyboard->setModule("wallet");
			$inlineKeyboard->setEntity("account");

			$response = $this->sendMessage(
				$chatId,
				"Apakah ingin menambahkan saldo awal ?",
				[
					"inline_keyboard" => $inlineKeyboard->confirmation("initial_balance"),
				],
				"Markdown",
				[],
				true
			);
			CacheReplyStateManager::forgetReply($chatId, $replyToMessageId);

			CacheReplyStateManager::expectReply(
				$chatId,
				$response->getMessageId(),
				$oldState["handler"],
				array_merge(["account_name" => $accountName], $oldState["context"])
			);

			return [
				"answer" => "Initial balance",
				"status" => "waiting_answer_reply",
			];
		} catch (\Exception $e) {
			Log::error("Failed to process reply message.", [
				"message" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
			]);
		}
	}

	private function ensureReplyToMessageIdExists(
		int $chatId,
		?int $replyToMessageId,
		?array $context = []
	): bool {
		if (!$replyToMessageId) {
			Log::error("Missing message ID.", [
				"chat_id" => $chatId,
				"message_id" => $replyToMessageId,
			]);

			$this->noticeUser($chatId, $context);

			return false;
		}

		return true;
	}

	private function noticeUser(int $chatId, array $context = []): void
	{
		$message = "Missing message ID or the message was expired.";
		$callbackQueryId = $context["callback_id"] ?? null;
		if ($callbackQueryId) {
			$this->answerCallbackQuery($callbackQueryId, $message, false);
		} else {
			$this->sendMessage($chatId, $message);
		}
	}
}
