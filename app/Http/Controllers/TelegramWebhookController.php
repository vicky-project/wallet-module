<?php
namespace Modules\Wallet\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Wallet\Services\Telegram\CommandService;
use Modules\Wallet\Services\Telegram\LinkService;
use Modules\Wallet\Services\Telegram\UpdateHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
	protected $telegram;
	protected $linkService;
	protected $commandService;
	protected $updateHandler;

	public function __construct(
		LinkService $linkService,
		CommandService $commandService,
		UpdateHandler $updateHandler
	) {
		$this->linkService = $linkService;
		$this->commandService = $commandService;
		$this->updateHandler = $updateHandler;
		$this->telegram = new Api(config("wallet.telegram_bot.token"));
	}

	/**
	 * Handle incoming webhook
	 */
	public function handleWebhook(Request $request)
	{
		Log::info("Telegram webhook received", [
			"ip" => $request->ip(),
			"user_agent" => $request->userAgent(),
		]);

		// Verify secret token if set
		if (config("wallet.telegram_bot.webhook_secret")) {
			$secret = $request->header("X-Telegram-Bot-Api-Secret-Token");
			if ($secret !== config("wallet.telegram_bot.webhook_secret")) {
				Log::warning("Invalid webhook secret", ["provided" => $secret]);
				abort(403, "Invalid secret token");
			}
		}

		try {
			$result = $this->updateHandler->handle($request);

			return response()->json(["status" => "ok", "result" => $result]);
		} catch (TelegramSDKException $e) {
			Log::error("Telegram SDK error", [
				"error" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
			]);
			return response()->json(["error" => "Internal error"], 500);
		} catch (\Exception $e) {
			Log::error("Webhook processing error", [
				"error" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
			]);
			return response()->json(["error" => "Processing error"], 500);
		}
	}

	/**
	 * Set webhook URL (public endpoint)
	 */
	public function setWebhook()
	{
		$this->validateAdmin();

		$url = url("/api/telegram/webhook");

		try {
			$response = $this->telegram->setWebhook([
				"url" => $url,
				"secret_token" => config("wallet telegram_bot.webhook_secret"),
				"max_connections" => 40,
				"allowed_updates" => ["message", "callback_query"],
			]);

			return response()->json([
				"success" => true,
				"url" => $url,
				"response" => $response,
			]);
		} catch (TelegramSDKException $e) {
			return response()->json(
				[
					"success" => false,
					"error" => $e->getMessage(),
				],
				500
			);
		}
	}

	/**
	 * Remove webhook
	 */
	public function removeWebhook()
	{
		$this->validateAdmin();

		try {
			$response = $this->telegram->removeWebhook();

			return response()->json([
				"success" => true,
				"response" => $response,
			]);
		} catch (TelegramSDKException $e) {
			return response()->json(
				[
					"success" => false,
					"error" => $e->getMessage(),
				],
				500
			);
		}
	}

	/**
	 * Get webhook info
	 */
	public function getWebhookInfo()
	{
		$this->validateAdmin();

		try {
			$info = $this->telegram->getWebhookInfo();

			return response()->json([
				"success" => true,
				"info" => [
					"url" => $info->getUrl(),
					"has_custom_certificate" => $info->getHasCustomCertificate(),
					"pending_update_count" => $info->getPendingUpdateCount(),
					"last_error_date" => $info->getLastErrorDate(),
					"last_error_message" => $info->getLastErrorMessage(),
					"max_connections" => $info->getMaxConnections(),
					"allowed_updates" => $info->getAllowedUpdates(),
				],
			]);
		} catch (TelegramSDKException $e) {
			return response()->json(
				[
					"success" => false,
					"error" => $e->getMessage(),
				],
				500
			);
		}
	}

	/**
	 * Validate admin access
	 */
	private function validateAdmin()
	{
		$admins = explode(",", env("TELEGRAM_ADMINS", ""));

		if (!in_array(auth()->id(), $admins)) {
			abort(403, "Unauthorized");
		}
	}

	/**
	 * Test endpoint
	 */
	public function test()
	{
		return response()->json([
			"status" => "ok",
			"timestamp" => now(),
			"bot_username" => config("wallet.telegram_bot.username"),
			"webhook_url" => url("/api/telegram/webhook"),
		]);
	}
}
