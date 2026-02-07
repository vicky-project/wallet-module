<?php
namespace Modules\Wallet\Telegram\Commands;

use Modules\Telegram\Services\TelegramService;
use Modules\Telegram\Services\Support\TelegramApi;

class AccountCommand
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
		return "account";
	}

	public function getDescription(): string
	{
		return "Show list of accounts";
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
}
