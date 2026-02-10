<?php
namespace Modules\Wallet\Telegram\Commands;

use Illuminate\Support\Facades\Log;
use Modules\Telegram\Services\TelegramService;
use Modules\Telegram\Services\Support\InlineKeyboardBuilder;
use Modules\Telegram\Services\Support\GlobalCallbackBuilder;
use Modules\Telegram\Services\Support\TelegramApi;
use Modules\Telegram\Services\Handlers\Commands\BaseCommandHandler;

class AccountCommand extends BaseCommandHandler
{
	protected TelegramService $service;
	protected InlineKeyboardBuilder $inlineKeyboard;

	public function __construct(
		TelegramService $service,
		TelegramApi $telegram,
		InlineKeyboardBuilder $inlineKeyboard
	) {
		parent::__construct($telegram);
		$this->service = $service;
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
					"status" => "accounts_failed",
					"reason" => "not_linked",
					"send_message" => ["text" => $message, "parse_mode" => "MarkdownV2"],
				];
			}

			$accounts = $user
				->accounts()
				->active()
				->get();

			if ($accounts->isEmpty()) {
				$this->inlineKeyboard->setModule("wallet");
				$this->inlineKeyboard->setEntity("account");

				$addAccountKeyboard = [
					[
						"text" => "➕️ Tambah akun baru",
						"value" => $user->id,
					],
				];

				$message =
					"📭 Anda belum memiliki akun.\n\nTambahkan akun untuk mulai mencatat transaksi.";

				return [
					"status" => "no_accounts",
					"send_message" => [
						"text" => $message,
						"reply_markup" => [
							"inline_keyboard" => $this->inlineKeyboard->grid(
								$addAccountKeyboard,
								2,
								"create"
							),
						],
					],
				];
			}

			$message = "🏦 *Daftar Akun Anda:*\n\n";
			foreach ($accounts as $account) {
				$balance = number_format($account->balance->getAmount()->toInt());
				$message .= "• @{$account->name} - Rp {$balance}\n";
			}
			$message .= "\nGunakan `@nama_akun` saat menambah transaksi.";

			return [
				"status" => "accounts_listed",
				"count" => $accounts->count(),
				"send_message" => [
					"text" => $message,
					"parse_mode" => "MarkdownV2",
					"reply_markup" => [
						"inline_keyboard" => $this->prepareAccountsKeyboard($accounts),
					],
					"auto_escape" => false,
				],
			];
		} catch (\Exception $e) {
			Log::error("Failed to get accounts list.", [
				"message" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
			]);

			return [
				"status" => "accounts_failed",
				"message" => $e->getMessage(),
				"send_message" => ["text" => $e->getMessage()],
			];
		}
	}

	private function prepareAccountsKeyboard($accounts)
	{
		$keyboard = [];
		foreach ($accounts as $account) {
			$keyboard[] = [
				"text" => $account->name,
				"value" => $account->id,
			];
		}

		$this->inlineKeyboard->setModule("wallet");
		$this->inlineKeyboard->setEntity("account");

		return $this->inlineKeyboard->grid($keyboard, 2, "detail");
	}
}
