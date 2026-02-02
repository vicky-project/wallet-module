<?php
namespace Modules\Wallet\Services\Telegram\Handlers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Wallet\Services\Telegram\Support\TelegramApi;

class ConversationHandler
{
	protected TelegramApi $telegramApi;
	protected LinkService $linkService;
	protected CommandService $commandService;

	public function __construct(
		TelegramApi $telegramApi,
		LinkService $linkService,
		CommandService $commandService
	) {
		$this->telegramApi = $telegramApi;
		$this->linkService = $linkService;
		$this->commandService = $commandService;
	}

	/**
	 * Start a conversation
	 */
	public function startConversation(
		int $chatId,
		string $conversationType,
		array $data = []
	): bool {
		$conversation = [
			"type" => $conversationType,
			"data" => $data,
			"step" => 1,
			"created_at" => now()->toDateTimeString(),
		];

		$key = $this->getConversationKey($chatId);
		Cache::put($key, $conversation, now()->addMinutes(30));

		Log::info("Conversation started", [
			"chat_id" => $chatId,
			"type" => $conversationType,
			"step" => 1,
		]);

		return true;
	}

	/**
	 * Get current conversation
	 */
	public function getConversation(int $chatId): ?array
	{
		$key = $this->getConversationKey($chatId);
		return Cache::get($key);
	}

	/**
	 * Handle conversation response
	 */
	public function handleResponse(int $chatId, string $response): array
	{
		$conversation = $this->getConversation($chatId);

		if (!$conversation) {
			return ["status" => "no_conversation"];
		}

		$type = $conversation["type"];
		$step = $conversation["step"];
		$data = $conversation["data"] ?? [];

		// Process based on conversation type
		$methodName = "handle" . ucfirst($type) . "Conversation";
		if (method_exists($this, $methodName)) {
			return $this->$methodName($chatId, $step, $response, $data);
		}

		// Default: end conversation
		$this->endConversation($chatId);

		return [
			"status" => "conversation_ended",
			"reason" => "unknown_type",
		];
	}

	/**
	 * Handle add transaction conversation
	 */
	private function handleAddConversation(
		int $chatId,
		int $step,
		string $response,
		array $data
	): array {
		switch ($step) {
			case 1: // Ask for transaction type
				$data["type"] = $response;
				$this->updateConversation($chatId, $data, 2);
				$this->telegramApi->sendMessage(
					$chatId,
					"💰 Masukkan jumlah transaksi:"
				);
				break;

			case 2: // Ask for amount
				if (!is_numeric($response)) {
					$this->telegramApi->sendMessage(
						$chatId,
						"❌ Jumlah harus angka. Coba lagi:"
					);
					return ["status" => "invalid_amount"];
				}

				$data["amount"] = $response;
				$this->updateConversation($chatId, $data, 3);
				$this->telegramApi->sendMessage(
					$chatId,
					"📝 Masukkan deskripsi transaksi:"
				);
				break;

			case 3: // Ask for description
				$data["description"] = $response;
				$this->updateConversation($chatId, $data, 4);

				$user = $this->linkService->getUserByChatId($chatId);
				$accounts = $user
					->accounts()
					->active()
					->get();

				$keyboard = [];
				foreach ($accounts as $account) {
					$keyboard[] = [
						[
							"text" => $account->name,
							"callback_data" => json_encode([
								"action" => "select_account",
								"type" => "conversation",
								"account_id" => $account->id,
							]),
						],
					];
				}

				$this->telegramApi->sendMessage($chatId, "🏦 Pilih akun:", "Markdown", [
					"inline_keyboard" => $keyboard,
				]);
				break;

			default:
				$this->endConversation($chatId);
				return ["status" => "conversation_completed"];
		}

		return [
			"status" => "conversation_continued",
			"step" => $step,
		];
	}

	/**
	 * Update conversation
	 */
	private function updateConversation(
		int $chatId,
		array $data,
		int $nextStep
	): void {
		$conversation = $this->getConversation($chatId);

		if ($conversation) {
			$conversation["data"] = array_merge($conversation["data"], $data);
			$conversation["step"] = $nextStep;
			$conversation["updated_at"] = now()->toDateTimeString();

			$key = $this->getConversationKey($chatId);
			Cache::put($key, $conversation, now()->addMinutes(30));
		}
	}

	/**
	 * End conversation
	 */
	public function endConversation(int $chatId): void
	{
		$key = $this->getConversationKey($chatId);
		Cache::forget($key);

		Log::info("Conversation ended", ["chat_id" => $chatId]);
	}

	/**
	 * Get conversation cache key
	 */
	private function getConversationKey(int $chatId): string
	{
		return "telegram_conversation:{$chatId}";
	}

	/**
	 * Get active conversations count
	 */
	public function getActiveConversationsCount(): int
	{
		$pattern = "telegram_conversation:*";

		// Note: This works with Redis, adjust for other cache drivers
		$keys = Cache::getStore()
			->getRedis()
			->keys($pattern);

		return count($keys);
	}
}
