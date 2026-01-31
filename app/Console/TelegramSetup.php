<?php
namespace Modules\Wallet\Console;

use Illuminate\Console\Command;
use Telegram\Bot\Api;
use Illuminate\Support\Facades\Log;

class TelegramSetup extends Command
{
	protected $signature = 'telegram:setup 
                            {action : set|remove|info|test} 
                            {--url= : Webhook URL}';

	protected $description = "Setup Telegram webhook";

	public function handle()
	{
		$token = env("TELEGRAM_BOT_TOKEN");

		if (!$token) {
			$this->error("TELEGRAM_BOT_TOKEN not set in .env");
			return 1;
		}

		$telegram = new Api($token);
		$action = $this->argument("action");

		switch ($action) {
			case "set":
				return $this->setWebhook($telegram);
			case "remove":
				return $this->removeWebhook($telegram);
			case "info":
				return $this->getWebhookInfo($telegram);
			case "test":
				return $this->testConnection($telegram);
			default:
				$this->error("Invalid action. Use: set, remove, info, test");
				return 1;
		}
	}

	private function setWebhook(Api $telegram): int
	{
		$url = $this->option("url") ?? url("/api/telegram/webhook");
		$secret = env("TELEGRAM_WEBHOOK_SECRET");

		$this->info("Setting webhook to: {$url}");

		try {
			$params = [
				"url" => $url,
				"max_connections" => 40,
				"allowed_updates" => ["message", "callback_query"],
			];

			if ($secret) {
				$params["secret_token"] = $secret;
				$this->info("Using secret token: {$secret}");
			}

			$response = $telegram->setWebhook($params);

			if ($response) {
				$this->info("✅ Webhook set successfully!");
				$this->showWebhookInfo($telegram);
				return 0;
			} else {
				$this->error("❌ Failed to set webhook");
				return 1;
			}
		} catch (\Exception $e) {
			$this->error("❌ Error: " . $e->getMessage());
			Log::error("Failed to set Telegram webhook", [
				"error" => $e->getMessage(),
			]);
			return 1;
		}
	}

	private function removeWebhook(Api $telegram): int
	{
		$this->info("Removing webhook...");

		try {
			$response = $telegram->removeWebhook();

			if ($response) {
				$this->info("✅ Webhook removed successfully!");
				return 0;
			} else {
				$this->error("❌ Failed to remove webhook");
				return 1;
			}
		} catch (\Exception $e) {
			$this->error("❌ Error: " . $e->getMessage());
			return 1;
		}
	}

	private function getWebhookInfo(Api $telegram): int
	{
		$this->showWebhookInfo($telegram);
		return 0;
	}

	private function showWebhookInfo(Api $telegram): void
	{
		try {
			$info = $telegram->getWebhookInfo();

			$this->info("\n📊 Webhook Information:");
			$this->line("URL: " . ($info->getUrl() ?: "Not set"));
			$this->line("Pending Updates: " . $info->getPendingUpdateCount());
			$this->line("Last Error: " . ($info->getLastErrorMessage() ?: "None"));
			$this->line("Max Connections: " . $info->getMaxConnections());

			if ($info->getLastErrorDate()) {
				$this->line(
					"Last Error Date: " . date("Y-m-d H:i:s", $info->getLastErrorDate())
				);
			}
		} catch (\Exception $e) {
			$this->error("Failed to get webhook info: " . $e->getMessage());
		}
	}

	private function testConnection(Api $telegram): int
	{
		$this->info("Testing Telegram connection...");

		try {
			$me = $telegram->getMe();

			$this->info("✅ Connection successful!");
			$this->line("Bot ID: " . $me->getId());
			$this->line("Bot Name: " . $me->getFirstName());
			$this->line("Bot Username: @" . $me->getUsername());

			return 0;
		} catch (\Exception $e) {
			$this->error("❌ Connection failed: " . $e->getMessage());
			return 1;
		}
	}
}
