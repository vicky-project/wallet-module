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
		$callbackId = $context["callback_id"];
		$user = $context["user"];
		$entity = $data["entity"];
		$action = $data["action"];
		$id = $data["id"];
		$params = $data["params"] ?? [];

		$message = "No message";
		switch ($entity) {
			case "account":
				return $this->handleAccountCallback(
					$context,
					$user,
					$action,
					$id,
					$params
				);
		}
	}

	private function handleAccountCallback(
		array $context,
		$user,
		string $action,
		$id,
		$params
	): array {
		$callback = app(AccountCallback::class);
		$message = $callback->action($user, $action, $id);
		$inlineKeyboard = app(InlineKeyboardBuilder::class);
		$inlineKeyboard->setScope($this->getScope());
		$inlineKeyboard->setModule($this->getModuleName());
		$inlineKeyboard->setEntity("account");

		return [
			"answer" => $message,
			"send_as_message" => true,
			"message_options" => [
				"inline_keyboard" => $inlineKeyboard->grid([["text" => ""]], 2, "help"),
			],
		];
	}
}
