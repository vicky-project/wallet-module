<?php
namespace Modules\Wallet\Telegram\Callbacks;

use Illuminate\Support\Facades\Log;
use Modules\Telegram\Services\Handlers\Callbacks\BaseCallbackHandler;
use Modules\Telegram\Services\Support\TelegramApi;

class CallbackHandler extends BaseCallbackHandler
{
	protected $telegram;

	public function __construct(TelegramApi $telegram)
	{
		$this->telegram = $telegram;
	}

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
			$callbackId = $context["callback_id"];
			$user = $context["user"];
			$entity = $data["entity"];
			$action = $data["action"];
			$id = $data["id"];
			$params = $data["params"] ?? [];

			$message = "No message";
			switch ($entity) {
				case "account":
					$callback = app(AccountCallback::class);
					$message = $callback->action($user, $action, $id);
					break;
			}

			$this->telegram->answerCallbackQuery($callbackId, $message);

			return ["status" => "callback_handled", "entity" => $entity];
		} catch (\Exception $e) {
			Log::error("Failed to handle callback of account", [
				"message" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
			]);

			return ["status" => "callback_failed", "answer" => $e->getMessage()];
		}
	}
}
