<?php
namespace Modules\Wallet\Services\Telegram\Handlers;

use Illuminate\Support\Facades\Log;
use Modules\Telegram\Services\TelegramService;
use Modules\Telegram\Services\Support\TelegramApi;
use Modules\Telegram\Interfaces\TelegramCommandInterface;

class AddCommand implements TelegramCommandInterface
{
	protected $appName;
	protected TelegramApi $telegram;
	protected TelegramService $service;

	public function __construct(TelegramApi $telegram, TelegramService $service)
	{
		$this->telegram = $telegram;
		$this->service = $service;
		$this->appName = config("app.name", "Financial");
	}
	public function getName(): string
	{
		return "add";
	}

	public function getDescription(): string
	{
		return "Add transaction to wallet";
	}

	/**
	 * Handle command
	 */
	public function handle(
		int $chatId,
		string $text,
		?string $username = null,
		array $params = []
	): array {
		try {
			$user = $params["user"] ?? null;

			if (!$user) {
				$user = $this->service->getUserByChatId($chatId);
			}

			if (!$user) {
				$message =
					"❌ Anda belum terhubung.\n" .
					"Gunakan /start untuk instruksi linking.";

				$this->telegram->sendMessage($chatId, $message);

				return [
					"status" => "add_failed",
					"reason" => "not_linked",
				];
			}

			$fullText = "/add " . ($argument ?? "");
			$result = $this->processAddCommand($user, $fullText);

			$this->telegram->sendMessage(
				$chatId,
				$result["message"],
				$result["parse_mode"] ?? null
			);

			return [
				"status" => "add_processed",
				"success" => $result["success"] ?? false,
				"transaction_id" => $result["transaction_id"] ?? null,
			];
		} catch (\Exception $e) {
			Log::error("Failed to add transaction.", [
				"message" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
			]);

			return [
				"status" => "faild_add_processed",
				"success" => false,
				"message" => $e->getMessage(),
			];
		}
	}

	/**
	 * Process /add command
	 */
	private function processAddCommand(User $user, string $text): array
	{
		// Parse command: /add <type> <amount> <description> [#category] [@account]
		$parts = explode(" ", $text, 5);

		if (count($parts) < 4) {
			return [
				"success" => false,
				"message" => $this->getAddCommandUsage(),
				"parse_mode" => "Markdown",
			];
		}

		try {
			$type = strtolower($parts[1]);
			$amount = $this->parseAmount($parts[2]);
			$description = $parts[3];

			// Extract optional parameters
			preg_match("/#(\w+)/", $text, $categoryMatch);
			preg_match("/@(\w+)/", $text, $accountMatch);

			$categoryName = $categoryMatch[1] ?? "Umum";
			$accountName = $accountMatch[1] ?? "Default";

			// Prepare transaction data
			$transactionData = [
				"type" => $type,
				"amount" => $amount,
				"description" => $description,
				"category_id" => $this->getCategoryId($user, $categoryName),
				"account_id" => $this->getAccountId($user, $accountName),
				"transaction_date" => now()->format("Y-m-d H:i:s"),
			];

			// Use existing TransactionService
			$result = $this->transactionService->createTransaction(
				$transactionData,
				$user
			);

			if ($result["success"]) {
				return [
					"success" => true,
					"message" => $this->formatSuccessMessage(
						$result,
						$amount,
						$description,
						$categoryName,
						$accountName
					),
					"parse_mode" => "Markdown",
				];
			} else {
				return [
					"success" => false,
					"message" => "❌ Gagal: " . $result["message"],
				];
			}
		} catch (\Exception $e) {
			Log::error("Telegram add command error", [
				"user_id" => $user->id,
				"error" => $e->getMessage(),
			]);

			throw $e;
		}
	}

	/**
	 * Parse amount from string
	 */
	private function parseAmount(string $amountStr): int
	{
		// Remove non-numeric characters except minus
		$amount = preg_replace("/[^0-9\-]/", "", $amountStr);

		if (!is_numeric($amount)) {
			throw new \Exception("Jumlah harus berupa angka");
		}

		return (int) $amount;
	}

	/**
	 * Get usage instructions for /add command
	 */
	private function getAddCommandUsage(): string
	{
		$message = "❌ *Format salah!*\n\n";

		return $message . $this->messageBuilder->buildAddCommandUsage();
	}

	/**
	 * Handle /accounts command
	 */
	private function handleAccounts(
		int $chatId,
		?string $argument,
		?string $username
	): array {
		$user = $this->linkService->getUserByChatId($chatId);

		if (!$user) {
			$this->telegramApi->sendMessage($chatId, "❌ Anda belum terhubung.");

			return [
				"status" => "accounts_failed",
				"reason" => "not_linked",
			];
		}

		$accounts = $user
			->accounts()
			->active()
			->get();

		if ($accounts->isEmpty()) {
			$this->telegramApi->sendMessage($chatId, "📭 Anda belum memiliki akun.");

			return ["status" => "no_accounts"];
		}

		$message = "🏦 *Daftar Akun Anda:*\n\n";
		foreach ($accounts as $account) {
			$balance = number_format($account->balance->getAmount()->toInt());
			$message .= "• @{$account->name} - Rp {$balance}\n";
		}
		$message .= "\nGunakan `@nama_akun` saat menambah transaksi.";

		$this->telegramApi->sendMessage($chatId, $message, "Markdown");

		return [
			"status" => "accounts_listed",
			"count" => $accounts->count(),
		];
	}

	/**
	 * Handle /categories command
	 */
	private function handleCategories(
		int $chatId,
		?string $argument,
		?string $username
	): array {
		$user = $this->linkService->getUserByChatId($chatId);

		if (!$user) {
			$this->telegramApi->sendMessage($chatId, "❌ Anda belum terhubung.");

			return [
				"status" => "categories_failed",
				"reason" => "not_linked",
			];
		}

		$categories = $user
			->categories()
			->active()
			->get();

		if ($categories->isEmpty()) {
			$this->telegramApi->sendMessage(
				$chatId,
				"📭 Anda belum memiliki kategori."
			);

			return ["status" => "no_categories"];
		}

		$message = "📂 *Daftar Kategori Anda:*\n\n";
		foreach ($categories as $category) {
			$type = $category->type->value === "income" ? "📈" : "📉";
			$message .= "• #{$category->name} {$type}\n";
		}
		$message .= "\nGunakan `#nama_kategori` saat menambah transaksi.";

		$this->telegramApi->sendMessage($chatId, $message, "Markdown");

		return [
			"status" => "categories_listed",
			"count" => $categories->count(),
		];
	}

	/**
	 * Handle unknown command
	 */
	private function handleUnknownCommand(int $chatId): array
	{
		$message = "❌ Command tidak dikenali. Gunakan /help untuk bantuan.";
		$this->telegramApi->sendMessage($chatId, $message);

		return ["status" => "unknown_command"];
	}

	/**
	 * Get welcome message for linked user
	 */
	private function getWelcomeMessageForLinkedUser($user): string
	{
		return "👋 Halo {$user->name}!\n" .
			"Akun Anda sudah terhubung.\n\n" .
			"📋 *Command yang tersedia:*\n" .
			"• /add - Tambah transaksi baru\n" .
			"• /accounts - Lihat daftar akun\n" .
			"• /categories - Lihat kategori\n" .
			"• /unlink - Putuskan koneksi\n" .
			"• /help - Bantuan lengkap\n\n" .
			"Gunakan /add untuk menambah transaksi pertama Anda!";
	}

	/**
	 * Get welcome message for new user
	 */
	private function getWelcomeMessageForNewUser(): string
	{
		return "👋 Selamat datang di {$this->appName} Bot!\n\n" .
			"Untuk menghubungkan akun Anda:\n" .
			"1. Login ke aplikasi web\n" .
			"2. Buka Menu Financial -> Settings → Telegram Integration\n" .
			"3. Generate kode verifikasi\n" .
			"4. Kirim ke bot ini: /link <kode>\n\n" .
			"Gunakan /help untuk informasi lebih lanjut.";
	}

	/**
	 * Get help message
	 */
	private function getHelpMessage(): string
	{
		return "📚 *Bantuan {$this->appName} Bot*\n\n" .
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
	}
}
