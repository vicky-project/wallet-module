<?php
namespace Modules\Wallet\Telegram\Callbacks;

use Illuminate\Support\Facades\Log;
use Modules\Telegram\Services\Handlers\Callbacks\BaseCallbackHandler;

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

		if (!$id) {
			return [
				"status" => "unknown_account",
				"answer" => "Kehilangan ID akun. Ketik perintah akun kembali.",
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
		$result = $callback->action($user, $action, $id, [
			"scope" => $this->getScope(),
			"module" => $this->getModuleName(),
			"entity" => "account",
		]);

		return [
			"status" => "success",
			"answer" => "Informasi akun berhasil dimuat",
			"edit_message" => $this->createEditMessageData(
				$result["message"],
				$result["keyboard"] ?? null,
				"MarkdownV2"
			),
		];
	}
}
