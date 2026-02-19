<?php
namespace Modules\Wallet\Telegram\Callbacks;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Modules\Telegram\Services\Support\TelegramApi;
use Modules\Telegram\Services\Handlers\Callbacks\BaseCallbackHandler;

class CallbackHandler extends BaseCallbackHandler
{
	public function __construct(TelegramApi $telegramApi)
	{
		parent::__construct($telegramApi);
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
			return $this->handleCallbackWithAutoAnswer(
				$context,
				$data,
				fn($data, $context) => $this->processCallback($data, $context)
			);
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
		try {
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
					$account = app(AccountCallback::class);
					return $account->handle($user, $action, $id, $params);

				case "category":
					return [];
				default:
					return [];
			}
		} catch (\Exception $e) {
			throw $e;
		}
	}
}
