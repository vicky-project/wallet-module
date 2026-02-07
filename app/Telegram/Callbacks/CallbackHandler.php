<?php
namespace Modules\Wallet\Telegram\Callbacks;

use Illuminate\Support\Facades\Log;
use Modules\Telegram\Services\Handlers\Callbacks\BaseCallbackHandler;
use Modules\Telegram\Services\Support\InlineKeyboardBuilder;

class CallbackHandler extends BaseCallbackHandler
{
	public function getModuleName(): string
	{
		return "wallet";
	}

	public function getName(): string
	{
		return "Account callback handler";
	}

	public function handle(array $data, array $context): array
	{
		try {
			return $this->handleCallbackWithAutoAnswer(
				$context,
				$data,
				fn($data, $context) => $this->processCallback($data, $context)
			);

			return ["status" => "callback_handled", "entity" => $entity];
		} catch (\Exception $e) {
			Log::error("Failed to handle callback of account", [
				"message" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
			]);

			return ["status" => "callback_failed", "answer" => $e->getMessage()];
		}
	}

	private function processCallback(array $data, array $context): array
	{
		$entity = $data["entity"];
		$action = $data["action"];
		$id = $data["id"] ?? null;
		$params = $data["params"] ?? [];
		$user = $context["user"] ?? null;

		if (!$user) {
			return [
				"status" => "unauthorized",
				"answer" => "Anda perlu login terlebih dahulu",
				"show_alert" => true,
			];
		}

		switch ($entity) {
			case "account":
				return $this->handleAccountCallback(
					$context,
					$user,
					$action,
					$id,
					$params
				);
			default:
				return [
					"status" => "unknown_entity",
					"answer" => "Entitas tidak dikenali",
					"show_alert" => false,
				];
		}
	}

	private function handleAccountCallback(
		array $context,
		$user,
		string $action,
		$id,
		array $params = []
	): array {
		$callback = app(AccountCallback::class);
		$message = $callback->action($user, $action, $id);

		$inlineKeyboard = app(InlineKeyboardBuilder::class);
		$inlineKeyboard->setScope($this->getScope());
		$inlineKeyboard->setModule($this->getModuleName());
		$inlineKeyboard->setEntity("account");
		$keyboard = [
			"inline_keyboard" => $inlineKeyboard->grid(
				[["text" => "❓️ Bantuan", "value" => null]],
				2,
				"help"
			),
		];

		return [
			"status" => "success",
			"answer" => "Detail akun berhasil dimuat",
			"edit_message" => $this->createEditMessageData($message, $keyboard),
		];
	}
}
