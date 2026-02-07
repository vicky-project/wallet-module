<?php
namespace Modules\Wallet\Telegram\Callbacks;

use Illuminate\Support\Facades\Log;
use Modules\Telegram\Services\Handlers\Callbacks\BaseCallbackHandler;

class AccountCallback extends BaseCallbackHandler
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
			Log::debug("Incoming callback account", [
				"data" => $data,
				"context" => $context,
				"parsed" => $this->parseModuleData($data, $context),
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
