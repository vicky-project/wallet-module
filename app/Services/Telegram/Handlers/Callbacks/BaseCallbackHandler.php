<?php
namespace Modules\Wallet\Services\Telegram\Handlers\Callbacks;

use App\Models\User;
use Telegram\Bot\Objects\CallbackQuery;
use Illuminate\Support\Facades\Log;
use Modules\Wallet\Services\Telegram\Support\TelegramApi;
use Modules\Wallet\Interfaces\CallbackHandlerInterface;

abstract class BaseCallbackHandler implements CallbackHandlerInterface
{
	protected TelegramApi $telegramApi;
	protected ?User $user = null;
	protected ?CallbackQuery $callbackQuery = null;

	public function __construct(TelegramApi $telegramApi)
	{
		$this->telegramApi = $telegramApi;
	}

	public function setContext(User $user, CallbackQuery $callbackQuery): void
	{
		$this->user = $user;
		$this->callbackQuery = $callbackQuery;
	}

	abstract public function handle(
		User $user,
		array $data,
		CallbackQuery $callbackQuery
	): array;

	public function supports(string $action, string $type): bool
	{
		return false; // To be implemented by child classes
	}

	protected function answerCallbackQuery(
		string $text,
		bool $showAlert = false
	): void {
		try {
			$this->telegramApi->answerCallbackQuery(
				$this->callbackQuery->getId(),
				$text,
				$showAlert
			);
		} catch (\Exception $e) {
			Log::error("Failed to answer callback query", [
				"error" => $e->getMessage(),
				"callback_query_id" => $this->callbackQuery->getId(),
			]);
		}
	}

	protected function editMessageText(
		string $text,
		?array $keyboard = null
	): void {
		try {
			$this->telegramApi->editMessageText(
				$this->callbackQuery
					->getMessage()
					->getChat()
					->getId(),
				$this->callbackQuery->getMessage()->getMessageId(),
				$text,
				$keyboard
			);
		} catch (\Exception $e) {
			Log::error("Failed to edit message text", [
				"error" => $e->getMessage(),
				"chat_id" => $this->callbackQuery
					->getMessage()
					->getChat()
					->getId(),
			]);
		}
	}

	protected function deleteMessage(): void
	{
		try {
			$this->telegramApi->deleteMessage(
				$this->callbackQuery
					->getMessage()
					->getChat()
					->getId(),
				$this->callbackQuery->getMessage()->getMessageId()
			);
		} catch (\Exception $e) {
			Log::error("Failed to delete message", [
				"error" => $e->getMessage(),
			]);
		}
	}

	protected function sendMessage(string $text, ?array $keyboard = null): void
	{
		try {
			$this->telegramApi->sendMessage(
				$this->callbackQuery
					->getMessage()
					->getChat()
					->getId(),
				$text,
				"Markdown",
				$keyboard
			);
		} catch (\Exception $e) {
			Log::error("Failed to send message", [
				"error" => $e->getMessage(),
			]);
		}
	}

	protected function validateUserOwnership(
		string $modelClass,
		$id,
		string $relation = "user_id"
	): ?object {
		$model = $modelClass
			::where("id", $id)
			->where($relation, $this->user->id)
			->first();

		if (!$model) {
			$this->answerCallbackQuery(
				"❌ Data tidak ditemukan atau tidak memiliki akses",
				true
			);
			return null;
		}

		return $model;
	}
}
