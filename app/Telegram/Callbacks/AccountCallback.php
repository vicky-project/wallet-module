<?php
namespace Modules\Wallet\Telegram\Callbacks;

use Illuminate\Support\Facades\Log;
use Modules\Telegram\Interfaces\TelegramCallbackHandlerInterface;

class AccountCallback implements TelegramCallbackHandlerInterface
{
	public function getPattern(): string
	{
		return "wallet:account:*";
	}

	public function getName(): string
	{
		return "Account callbac handler";
	}

	public function handle(array $data, array $context): array
	{
		try {
			Log::debug("Incoming callback account", [
				"data" => $data,
				"context" => $context,
			]);
			return [];
		} catch (\Exception $e) {
			Log::error("Failed to handle callback of account", [
				"message" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
			]);

			return ["success" => "failed", "answer" => $e->getMessage()];
		}
	}
}
