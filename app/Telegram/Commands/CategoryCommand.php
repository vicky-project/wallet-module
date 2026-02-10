<?php
namespace Modules\Wallet\Telegram\Commands;

use Illuminate\Support\Facades\Log;
use Modules\Wallet\Enums\CategoryType;
use Modules\Telegram\Services\TelegramService;
use Modules\Telegram\Services\Support\TelegramApi;
use Modules\Telegram\Services\Handlers\Commands\BaseCommandHandler;

class CategoryCommand extends BaseCommandHandler
{
	protected TelegramService $service;

	public function __construct(TelegramService $service, TelegramApi $telegram)
	{
		parent::__construct($telegram);

		$this->service = $service;
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
	protected function processCommand(
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

				return [
					"status" => "categories_failed",
					"reason" => "not_linked",
					"send_message" => ["text" => $message],
				];
			}

			$categories = $user
				->categories()
				->active()
				->get();

			if ($categories->isEmpty()) {
				return [
					"status" => "no_categories",
					"send_message" => ["text" => "📭 Anda belum memiliki kategori."],
				];
			}

			$message = "📂 *Daftar Kategori Anda:*\n\n";
			foreach ($categories as $category) {
				$type = $category->type === CategoryType::INCOME ? "📈" : "📉";
				$message .= "• #{$category->name} {$type}\n";
			}
			$message .= "\nGunakan `#nama_kategori` saat menambah transaksi.";

			return [
				"status" => "categories_listed",
				"count" => $categories->count(),
				"send_message" => ["text" => $message, "parse_mode" => "MarkdownV2"],
			];
		} catch (\Exception $e) {
			Log::error("Failed to get categories list.", [
				"message" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
			]);

			return [
				"status" => "categories_failed",
				"message" => $e->getMessage(),
				"send_message" => ["text" => $this - getErrorAnswer($e->getMessage())],
			];
		}
	}
}
