<?php
namespace Modules\Wallet\Services\Telegram\Handlers\Callbacks;

use App\Models\User;
use Telegram\Bot\Objects\CallbackQuery;
use Modules\Wallet\Models\Account;
use Modules\Wallet\Models\Transaction;
use Modules\Wallet\Services\Telegram\Builders\MessageBuilder;
use Modules\Wallet\Services\Telegram\Builders\KeyboardBuilder;
use Modules\Wallet\Services\Telegram\Support\TelegramApi;
use Modules\Wallet\Services\Telegram\Types\TransactionNotification;
use Illuminate\Support\Facades\Log;

class AccountCallbackHandler extends BaseCallbackHandler
{
	protected MessageBuilder $messageBuilder;
	protected KeyboardBuilder $keyboardBuilder;

	public function __construct(
		TelegramApi $telegramApi,
		MessageBuilder $messageBuilder,
		KeyboardBuilder $keyboardBuilder
	) {
		parent::__construct($telegramApi);
		$this->messageBuilder = $messageBuilder;
		$this->keyboardBuilder = $keyboardBuilder;
	}

	public function handle(
		User $user,
		array $data,
		CallbackQuery $callbackQuery
	): array {
		$this->setContext($user, $callbackQuery);

		$action = $data["action"] ?? null;
		$accountId = $data["id"] ?? null;

		$methodName = "handle" . ucfirst($action);
		if (!method_exists($this, $methodName)) {
			$this->answerCallbackQuery("❌ Aksi tidak didukung", true);
			return ["success" => false];
		}

		return $this->$methodName($accountId, $data);
	}

	public function supports(string $action, string $type): bool
	{
		return $type === "account";
	}

	private function handleView(string $accountId, array $data): array
	{
		$account = $this->validateUserOwnership(Account::class, $accountId);
		if (!$account) {
			return ["success" => false];
		}

		$message = $this->messageBuilder->buildAccountDetailMessage($account);
		$keyboard = $this->keyboardBuilder->buildAccountDetailKeyboard($account);

		$this->editMessageText($message, $keyboard);
		$this->answerCallbackQuery("🏦 Memuat detail akun...");

		return ["success" => true];
	}

	private function handleAddFunds(string $accountId, array $data): array
	{
		$account = $this->validateUserOwnership(Account::class, $accountId);
		if (!$account) {
			return ["success" => false];
		}

		$amount = $data["amount"] ?? null;

		if ($amount && is_numeric($amount)) {
			return $this->processAddFunds($account, $amount, $data["note"] ?? null);
		}

		// Show amount selection
		$message = $this->messageBuilder->buildAddFundsMessage($account);
		$keyboard = $this->keyboardBuilder->buildAddFundsKeyboard($accountId);

		$this->editMessageText($message, $keyboard);
		$this->answerCallbackQuery("💰 Pilih jumlah dana");

		return ["success" => true];
	}

	private function processAddFunds(
		Account $account,
		int $amount,
		?string $note = null
	): array {
		try {
			// Create income transaction
			$transaction = Transaction::create([
				"user_id" => $this->user->id,
				"account_id" => $account->id,
				"category_id" => 1, // Default income category
				"type" => "income",
				"amount" => $amount,
				"description" => "Tambah dana via Telegram",
				"transaction_date" => now(),
				"notes" => $note ?? "Ditambahkan melalui Telegram Bot",
			]);

			// Update account balance
			$account->balance = $account->balance->plus($amount);
			$account->save();

			$message = $this->messageBuilder->buildFundsAddedMessage(
				$account,
				$amount
			);
			$this->editMessageText($message);
			$this->answerCallbackQuery("✅ Dana berhasil ditambahkan");

			// Send notification about new transaction
			$this->sendNewTransactionNotification($transaction);

			return ["success" => true, "transaction" => $transaction];
		} catch (\Exception $e) {
			Log::error("Failed to add funds", [
				"account_id" => $account->id,
				"amount" => $amount,
				"error" => $e->getMessage(),
			]);

			$this->editMessageText(
				'❌ *Gagal menambah dana*\n\nTerjadi kesalahan sistem.'
			);
			$this->answerCallbackQuery("❌ Gagal menambah dana", true);

			return ["success" => false];
		}
	}

	private function sendNewTransactionNotification(
		Transaction $transaction
	): void {
		$notification = app(TransactionNotification::class);
		$notification->setContext($this->user, ["transaction" => $transaction]);
		$notification->send($this->user);
	}
}
