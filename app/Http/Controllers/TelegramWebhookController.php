<?php
namespace Modules\Wallet\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Wallet\Services\Telegram\CommandService;
use Modules\Wallet\Services\Telegram\LinkService;
use Modules\Wallet\Services\Telegram\NotificationService;
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
	protected $notificationService;

	public function __construct(
		LinkService $linkService,
		CommandService $commandService,
		NotificationService $notificationService
	) {
		$this->linkService = $linkService;
		$this->commandService = $commandService;
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
			$update = $this->telegram->getWebhookUpdate();

			if ($update->has("message")) {
				$this->handleMessage($update->getMessage());
			} elseif ($update->has("callback_query")) {
				$this->notificationService->handleCallbackQuery(
					$update->getCallbackQuery()
				);
			}

			return response()->json(["status" => "ok"]);
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
	 * Handle incoming messages
	 */
	private function handleMessage($message)
	{
		$chatId = $message->getChat()->getId();
		$text = $message->getText();
		$username = $message->getChat()->getUsername();

		Log::info("Telegram message received", [
			"chat_id" => $chatId,
			"username" => $username,
			"text" => $text,
		]);

		// Check if it's a command
		if (strpos($text, "/") === 0) {
			$this->handleCommand($chatId, $text, $username);
		} else {
			$this->handleTextMessage($chatId, $text, $username);
		}
	}

	/**
	 * Handle commands
	 */
	private function handleCommand(int $chatId, string $text, ?string $username)
	{
		$parts = explode(" ", $text, 2);
		$command = strtolower(trim($parts[0], "/"));
		$argument = $parts[1] ?? null;

		switch ($command) {
			case "start":
				$this->handleStartCommand($chatId, $username);
				break;
			case "link":
				$this->handleLinkCommand($chatId, $argument, $username);
				break;
			case "unlink":
				$this->handleUnlinkCommand($chatId);
				break;
			case "add":
				$this->handleAddCommand($chatId, $text);
				break;
			case "help":
				$this->handleHelpCommand($chatId);
				break;
			case "accounts":
				$this->handleAccountsCommand($chatId);
				break;
			case "categories":
				$this->handleCategoriesCommand($chatId);
				break;
			default:
				$this->sendMessage(
					$chatId,
					"❌ Command tidak dikenali. Gunakan /help untuk bantuan."
				);
		}
	}

	/**
	 * Handle /start command
	 */
	private function handleStartCommand(int $chatId, ?string $username)
	{
		$user = $this->linkService->getUserByChatId($chatId);

		if ($user) {
			$message =
				"👋 Halo {$user->name}!\n" .
				"Akun Anda sudah terhubung.\n\n" .
				"📋 *Command yang tersedia:*\n" .
				"• /add - Tambah transaksi baru\n" .
				"• /accounts - Lihat daftar akun\n" .
				"• /categories - Lihat kategori\n" .
				"• /unlink - Putuskan koneksi\n" .
				"• /help - Bantuan lengkap\n\n" .
				"Gunakan /add untuk menambah transaksi pertama Anda!";
		} else {
			$message =
				"👋 Selamat datang di Finance Bot!\n\n" .
				"Untuk menghubungkan akun Anda:\n" .
				"1. Login ke aplikasi web\n" .
				"2. Buka Settings → Telegram Integration\n" .
				"3. Generate kode verifikasi\n" .
				"4. Kirim ke bot ini: /link <kode>\n\n" .
				"Gunakan /help untuk informasi lebih lanjut.";
		}

		$this->sendMessage($chatId, $message, "Markdown");
	}

	/**
	 * Handle /link command
	 */
	private function handleLinkCommand(
		int $chatId,
		?string $code,
		?string $username
	) {
		if (!$code) {
			$this->sendMessage(
				$chatId,
				"❌ Format salah.\n" .
					"Gunakan: /link <kode_verifikasi>\n\n" .
					"Dapatkan kode dari web app di halaman Settings → Telegram Integration."
			);
			return;
		}

		$user = $this->linkService->validateLinkingCode($code);

		if (!$user) {
			$this->sendMessage(
				$chatId,
				"❌ Kode tidak valid atau sudah kadaluarsa.\n" .
					"Silakan generate kode baru di web app."
			);
			return;
		}

		// Check if Telegram already linked to another account
		$existingUser = $this->linkService->getUserByChatId($chatId);
		if ($existingUser && $existingUser->id !== $user->id) {
			$this->sendMessage(
				$chatId,
				"❌ Akun Telegram ini sudah terhubung dengan akun lain.\n" .
					"Gunakan /unlink di akun tersebut terlebih dahulu."
			);
			return;
		}

		// Complete linking
		$success = $this->linkService->completeLinking($user, $chatId, $username);

		if ($success) {
			$this->sendMessage(
				$chatId,
				"✅ *Akun berhasil dihubungkan!*\n\n" .
					"Halo {$user->name}!\n" .
					"Sekarang Anda bisa menambah transaksi via Telegram.\n\n" .
					"Contoh: `/add expense 50000 Makan siang #Food @Cash`\n" .
					"Gunakan /help untuk command lengkap.",
				"Markdown"
			);
		} else {
			$this->sendMessage($chatId, "❌ Gagal menghubungkan akun. Coba lagi.");
		}
	}

	/**
	 * Handle /unlink command
	 */
	private function handleUnlinkCommand(int $chatId)
	{
		$user = $this->linkService->getUserByChatId($chatId);

		if (!$user) {
			$this->sendMessage($chatId, "❌ Akun tidak terhubung.");
			return;
		}

		$user->unlinkTelegramAccount();

		$this->sendMessage(
			$chatId,
			"✅ *Akun berhasil diputuskan.*\n\n" .
				"Anda bisa menghubungkan kembali melalui web app.\n" .
				"Terima kasih telah menggunakan bot kami! 👋",
			"Markdown"
		);
	}

	/**
	 * Handle /add command (menggunakan service yang ada)
	 */
	private function handleAddCommand(int $chatId, string $text)
	{
		$user = $this->linkService->getUserByChatId($chatId);

		if (!$user) {
			$this->sendMessage(
				$chatId,
				"❌ Anda belum terhubung.\n" . "Gunakan /start untuk instruksi linking."
			);
			return;
		}

		// Parse dan proses transaksi menggunakan command service
		$result = $this->commandService->processAddCommand($user, $text);

		$this->sendMessage(
			$chatId,
			$result["message"],
			$result["parse_mode"] ?? null
		);
	}

	/**
	 * Handle other commands
	 */
	private function handleHelpCommand(int $chatId)
	{
		$message =
			"📚 *Bantuan Finance Bot*\n\n" .
			"🔗 *Linking Account:*\n" .
			"• /start - Memulai bot\n" .
			"• /link <kode> - Hubungkan akun\n" .
			"• /unlink - Putuskan koneksi\n\n" .
			"💰 *Transaksi:*\n" .
			"• /add <tipe> <jumlah> <deskripsi> [#kategori] [@akun]\n" .
			"  Contoh: `/add expense 50000 Makan siang #Food @Cash`\n\n" .
			"📊 *Informasi:*\n" .
			"• /accounts - Lihat daftar akun\n" .
			"• /categories - Lihat kategori\n\n" .
			"⚙️ *Format:*\n" .
			"• Tipe: income, expense, transfer\n" .
			"• Jumlah: angka tanpa titik (50000)\n" .
			"• #kategori: opsional (default: Umum)\n" .
			"• @akun: opsional (default: akun utama)\n\n" .
			"💡 *Tips:* Gunakan tanpa spasi untuk nama multi-kata\n" .
			"Contoh: `#MakanSiang` atau `@BankBCA`";

		$this->sendMessage($chatId, $message, "Markdown");
	}

	private function handleAccountsCommand(int $chatId)
	{
		$user = $this->linkService->getUserByChatId($chatId);

		if (!$user) {
			$this->sendMessage($chatId, "❌ Anda belum terhubung.");
			return;
		}

		$accounts = $user
			->accounts()
			->active()
			->get();

		if ($accounts->isEmpty()) {
			$this->sendMessage($chatId, "📭 Anda belum memiliki akun.");
			return;
		}

		$message = "🏦 *Daftar Akun Anda:*\n\n";
		foreach ($accounts as $account) {
			$balance = number_format($account->balance->getAmount()->toInt());
			$message .= "• @{$account->name} - Rp {$balance}\n";
		}
		$message .= "\nGunakan `@nama_akun` saat menambah transaksi.";

		$this->sendMessage($chatId, $message, "Markdown");
	}

	private function handleCategoriesCommand(int $chatId)
	{
		$user = $this->linkService->getUserByChatId($chatId);

		if (!$user) {
			$this->sendMessage($chatId, "❌ Anda belum terhubung.");
			return;
		}

		$categories = $user
			->categories()
			->active()
			->get();

		if ($categories->isEmpty()) {
			$this->sendMessage($chatId, "📭 Anda belum memiliki kategori.");
			return;
		}

		$message = "📂 *Daftar Kategori Anda:*\n\n";
		foreach ($categories as $category) {
			$type = $category->type->value === "income" ? "📈" : "📉";
			$message .= "• #{$category->name} {$type}\n";
		}
		$message .= "\nGunakan `#nama_kategori` saat menambah transaksi.";

		$this->sendMessage($chatId, $message, "Markdown");
	}

	/**
	 * Handle regular text messages
	 */
	private function handleTextMessage(
		int $chatId,
		string $text,
		?string $username
	) {
		// You can implement conversation flow here
		$this->sendMessage(
			$chatId,
			"Halo! Saya adalah bot untuk manajemen keuangan.\n" .
				"Gunakan /help untuk melihat command yang tersedia."
		);
	}

	/**
	 * Send message to Telegram
	 */
	private function sendMessage(
		int $chatId,
		string $text,
		?string $parseMode = null
	) {
		try {
			$params = [
				"chat_id" => $chatId,
				"text" => $text,
				"parse_mode" => $parseMode,
			];

			$this->telegram->sendMessage($params);
		} catch (\Exception $e) {
			Log::error("Failed to send Telegram message", [
				"chat_id" => $chatId,
				"error" => $e->getMessage(),
			]);
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
