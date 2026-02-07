<?php
namespace Modules\Wallet\Telegram\Commands;

use Illuminate\Support\Facades\Log;
use Modules\Telegram\Services\TelegramService;
use Modules\Telegram\Services\Support\InlineKeyboardBuilder;
use Modules\Telegram\Services\Support\TelegramApi;
use Modules\Telegram\Interfaces\TelegramCommandInterface;

class AccountCommand implements TelegramCommandInterface
{
	protected TelegramService $service;
	protected TelegramApi $telegram;
	protected InlineKeyboardBuilder $inlineKeyboard;

	public function __construct(
		TelegramService $service,
		TelegramApi $telegram,
		InlineKeyboardBuilder $inlineKeyboard
	) {
		$this->service = $service;
		$this->telegram = $telegram;
		$this->inlineKeyboard = $inlineKeyboard;
	}

	public function getName(): string
	{
		return "accounts";
	}

	public function getDescription(): string
	{
		return "Show accounts list";
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
					"status" => "accounts_failed",
					"reason" => "not_linked",
				];
			}

			$accounts = $user
				->accounts()
				->active()
				->get();

			if ($accounts->isEmpty()) {
				$addAccountKeyboard = [
					[
						[
							"text" => "➕️ Tambah akun baru",
							",callback_data" => "wallet:account:create",
						],
					],
				];
				$this->telegram->sendMessage(
					$chatId,
					"📭 Anda belum memiliki akun.\n\nTambahkan akun untuk mulai mencatat transaksi.",
					["inline_keyboard" => $addAccountKeyboard]
				);

				return ["status" => "no_accounts"];
			}

			$message = "🏦 *Daftar Akun Anda:*\n\n";
			foreach ($accounts as $account) {
				$balance = number_format($account->balance->getAmount()->toInt());
				$message .= "• @{$account->name} - Rp {$balance}\n";
			}
			$message .= "\nGunakan `@nama_akun` saat menambah transaksi.";

			$this->telegram->sendMessage($chatId, $message, "Markdown", [
				"inline_keyboard" => $this->prepareAccountsKeyboard($accounts),
			]);

			return [
				"status" => "accounts_listed",
				"count" => $accounts->count(),
			];
		} catch (\Exception $e) {
			Log::error("Failed to get accounts list.", [
				"message" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
			]);

			return ["status" => "accounts_failed", "message" => $e->getMessage()];
		}
	}

	private function prepareAccountsKeyboard($accounts)
	{
		$keyboard = [];
		foreach ($accounts as $account) {
			$keyboard[] = [
				["text" => $account->name, "value" => ["id" => $account->id]],
			];
		}

		return $this->inlineKeyboard->grid($keyboard, 2, "wallet:account:detail");
	}
}
