<?php
namespace Modules\Wallet\Telegram\Commands;

use Illuminate\Support\Facades\Log;
use Modules\Wallet\Enums\CategoryType;
use Modules\Telegram\Services\TelegramService;
use Modules\Telegram\Services\Support\TelegramApi;
use Modules\Telegram\Interfaces\TelegramCommandInterface;

class CategoryCommand implements TelegramCommandInterface
{
	protected TelegramService $service;
	protected TelegramApi $telegram;

	public function __construct(TelegramService $service, TelegramApi $telegram)
	{
		$this->service = $service;
		$this->telegram = $telegram;
	}

	public function getName(): string
	{
		return "categories";
	}

	public function getDescription(): string
	{
		return "Show list of categories";
	}

	/*
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
					"status" => "categories_failed",
					"reason" => "not_linked",
				];
			}

			$categories = $user
				->categories()
				->active()
				->get();

			if ($categories->isEmpty()) {
				$this->telegram->sendMessage(
					$chatId,
					"📭 Anda belum memiliki kategori."
				);

				return ["status" => "no_categories"];
			}

			$message = "📂 *Daftar Kategori Anda:*\n\n";
			foreach ($categories as $category) {
				$type = $category->type === CategoryType::INCOME ? "📈" : "📉";
				$message .= "• #{$category->name} {$type}\n";
			}
			$message .= "\nGunakan `#nama_kategori` saat menambah transaksi.";

			$this->telegram->sendMessage($chatId, $message, "Markdown");

			return [
				"status" => "categories_listed",
				"count" => $categories->count(),
			];
		} catch (\Exception $e) {
			Log::error("Failed to get categories list.", [
				"message" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
			]);

			return ["status" => "categories_failed", "message" => $e->getMessage()];
		}
	}
}
