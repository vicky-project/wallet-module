<?php
namespace Modules\Wallet\Telegram\Middlewares;

use Modules\Wallet\Services\AccountService;
use Modules\Telegram\Interfaces\TelegramMiddlewareInterface;
use Modules\Telegram\Services\TelegramService;

class AccountCallbackMiddleware implements TelegramMiddlewareInterface
{
	protected AccountService $accountService;
	protected TelegramService $telegramService;

	public function __construct(
		AccountService $accountService,
		TelegramService $telegramService
	) {
		$this->accountService = $accountService;
		$this->telegramService = $telegramService;
	}

	public function handle(array $context, callable $next)
	{
		$userId = $context["user_id"] ?? $context["chat_id"];
		$callbackData = $context["callback_data"];
		$user = $this->telegramService->getUserByChatId($userId);

		if (!$user) {
			\Log::warning("User not authenticated", [
				"chat_id" => $chatId,
				"username" => $username,
			]);

			if (!isset($context["callback_id"])) {
				$message =
					"❌ Anda belum terhubung.\n" .
					"Gunakan /start untuk instruksi linking.";

				$this->telegram->sendMessage($chatId, $message);
			}

			return [
				"answer" => isset($context["callback_id"]) ? "UnAuthorized user" : null,
				"status" => "unauthorized",
				"message" =>
					"Anda perlu mendaftar terlebih dahulu. Gunakan /register untuk mendaftar.",
				"chat_id" => $chatId,
				"block_handler" => true,
			];
		}

		if (strpos($callbackData, "wallet:account:") !== false) {
			preg_match("/wallet:account:(\d+)/", $callbackData, $matches);

			if (isset($matches[1])) {
				$accountId = $matches[1];
				$account = $this->accountService->getRepository()->find($accountId);

				if (!$account || $account->user_id !== $user->id) {
					return [
						"answer" => "You don't have access to this account.",
						"block_handler" => true,
					];
				}

				$context["account"] = $account;
			}
		}

		$context["user"] = $user;

		return $next($context);
	}
}
