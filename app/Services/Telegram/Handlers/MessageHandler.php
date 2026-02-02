<?php
namespace Modules\Wallet\Services\Telegram\Handlers;

use Telegram\Bot\Objects\Message;
use Illuminate\Support\Facades\Log;
use Modules\Wallet\Services\Telegram\Handlers\CommandHandler;
use Modules\Wallet\Services\Telegram\Handlers\ConversationHandler;
use Modules\Wallet\Services\Telegram\Support\TelegramApi;

class MessageHandler
{
	protected CommandHandler $commandHandler;
	protected ConversationHandler $conversationHandler;
	protected TelegramApi $telegramApi;

	public function __construct(
		CommandHandler $commandHandler,
		ConversationHandler $conversationHandler,
		TelegramApi $telegramApi
	) {
		$this->commandHandler = $commandHandler;
		$this->conversationHandler = $conversationHandler;
		$this->telegramApi = $telegramApi;
	}

	/**
	 * Handle incoming message
	 */
	public function handle(Message $message): array
	{
		$chatId = $message->getChat()->getId();
		$text = $message->getText() ?? "";
		$username = $message->getChat()->getUsername();

		Log::info("Telegram message received", [
			"chat_id" => $chatId,
			"username" => $username,
			"text" => $text,
		]);

		// Check if user is in conversation
		$conversation = $this->conversationHandler->getConversation($chatId);

		if ($conversation) {
			return $this->conversationHandler->handleResponse($chatId, $text);
		}

		// Handle command
		if ($this->isCommand($text)) {
			return $this->commandHandler->handleCommand($chatId, $text, $username);
		}

		// Handle regular text message
		return $this->handleTextMessage($chatId, $text);
	}

	/**
	 * Handle edited message
	 */
	public function handleEditedMessage(Message $message): array
	{
		Log::info("Telegram edited message", [
			"chat_id" => $message->getChat()->getId(),
			"message_id" => $message->getMessageId(),
		]);

		return ["status" => "edited_message_ignored"];
	}

	/**
	 * Check if text is a command
	 */
	private function isCommand(string $text): bool
	{
		return strpos($text, "/") === 0;
	}

	/**
	 * Handle regular text messages
	 */
	private function handleTextMessage(int $chatId, string $text): array
	{
		$response =
			"Halo! Saya adalah bot untuk manajemen keuangan.\n" .
			"Gunakan /help untuk melihat command yang tersedia.";

		$this->telegramApi->sendMessage($chatId, $response);

		return [
			"status" => "text_message",
			"chat_id" => $chatId,
			"response" => $response,
		];
	}

	/**
	 * Get chat information
	 */
	public function getChatInfo(int $chatId): ?array
	{
		try {
			$chat = $this->telegramApi->getChat($chatId);

			return [
				"id" => $chat->getId(),
				"type" => $chat->getType(),
				"title" => $chat->getTitle(),
				"username" => $chat->getUsername(),
				"first_name" => $chat->getFirstName(),
				"last_name" => $chat->getLastName(),
			];
		} catch (\Exception $e) {
			Log::error("Failed to get chat info", [
				"chat_id" => $chatId,
				"error" => $e->getMessage(),
			]);

			return null;
		}
	}
}
