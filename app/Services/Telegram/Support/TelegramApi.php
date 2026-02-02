<?php
namespace Modules\Wallet\Services\Telegram\Support;

use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Illuminate\Support\Facades\Log;

class TelegramApi
{
	protected ?Api $telegram;

	public function __construct()
	{
		$token = config("wallet.telegram_bot.token");
		if ($token) {
			$this->telegram = new Api($token);
		}
	}

	public function sendMessage(
		int $chatId,
		string $text,
		string $parseMode = "Markdown",
		?array $replyMarkup = null,
		array $options = []
	): bool {
		try {
			$params = [
				"chat_id" => $chatId,
				"text" => $text,
				"parse_mode" => $parseMode,
				"disable_web_page_preview" => $options["disable_preview"] ?? true,
			];

			if ($replyMarkup) {
				$params["reply_markup"] = json_encode($replyMarkup);
			}

			$this->telegram->sendMessage($params);
			return true;
		} catch (TelegramSDKException $e) {
			Log::error("Failed to send Telegram message", [
				"chat_id" => $chatId,
				"error" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
			]);
			return false;
		}
	}

	public function editMessageText(
		int $chatId,
		int $messageId,
		string $text,
		?array $replyMarkup = null
	): bool {
		try {
			$params = [
				"chat_id" => $chatId,
				"message_id" => $messageId,
				"text" => $text,
				"parse_mode" => "Markdown",
			];

			if ($replyMarkup) {
				$params["reply_markup"] = json_encode($replyMarkup);
			}

			$this->telegram->editMessageText($params);
			return true;
		} catch (TelegramSDKException $e) {
			Log::error("Failed to edit Telegram message", [
				"chat_id" => $chatId,
				"message_id" => $messageId,
				"error" => $e->getMessage(),
			]);
			return false;
		}
	}

	public function deleteMessage(int $chatId, int $messageId): bool
	{
		try {
			$this->telegram->deleteMessage([
				"chat_id" => $chatId,
				"message_id" => $messageId,
			]);
			return true;
		} catch (TelegramSDKException $e) {
			Log::error("Failed to delete Telegram message", [
				"chat_id" => $chatId,
				"message_id" => $messageId,
				"error" => $e->getMessage(),
			]);
			return false;
		}
	}

	public function answerCallbackQuery(
		string $callbackQueryId,
		string $text,
		bool $showAlert = false
	): bool {
		try {
			$this->telegram->answerCallbackQuery([
				"callback_query_id" => $callbackQueryId,
				"text" => $text,
				"show_alert" => $showAlert,
			]);
			return true;
		} catch (TelegramSDKException $e) {
			Log::error("Failed to answer callback query", [
				"callback_query_id" => $callbackQueryId,
				"error" => $e->getMessage(),
			]);
			return false;
		}
	}
}
