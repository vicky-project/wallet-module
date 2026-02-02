<?php
namespace Modules\Wallet\Services\Telegram\Handlers\Callbacks;

use App\Models\User;
use Telegram\Bot\Objects\CallbackQuery;
use Modules\Wallet\Models\Transaction;
use Modules\Wallet\Models\Category;
use Modules\Wallet\Services\Telegram\Builders\MessageBuilder;
use Modules\Wallet\Services\Telegram\Builders\KeyboardBuilder;
use Modules\Wallet\Services\Telegram\Support\TelegramApi;
use Illuminate\Support\Facades\Log;

class TransactionCallbackHandler extends BaseCallbackHandler
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
		$transactionId = $data["id"] ?? null;

		$methodName = "handle" . ucfirst($action);
		if (!method_exists($this, $methodName)) {
			$this->answerCallbackQuery("❌ Aksi transaksi tidak didukung", true);
			return ["success" => false];
		}

		return $this->$methodName($transactionId, $data);
	}

	public function supports(string $action, string $type): bool
	{
		return $type === "transaction";
	}

	private function handleView(string $transactionId, array $data): array
	{
		$transaction = $this->validateUserOwnership(
			Transaction::class,
			$transactionId
		);
		if (!$transaction) {
			return ["success" => false];
		}

		$message = $this->messageBuilder->buildTransactionDetailMessage(
			$transaction
		);
		$keyboard = $this->keyboardBuilder->buildTransactionDetailKeyboard(
			$transaction
		);

		$this->editMessageText($message, $keyboard);
		$this->answerCallbackQuery("📄 Memuat detail transaksi...");

		return ["success" => true];
	}

	private function handleRefresh(string $transactionId, array $data): array
	{
		// Memuat ulang detail transaksi yang sama
		return $this->handleView($transactionId, $data);
	}

	private function handleEdit(string $transactionId, array $data): array
	{
		$transaction = $this->validateUserOwnership(
			Transaction::class,
			$transactionId
		);
		if (!$transaction) {
			return ["success" => false];
		}

		$field = $data["field"] ?? null;

		if ($field) {
			return $this->handleEditField($transaction, $field, $data);
		}

		// Show edit options
		$message = $this->messageBuilder->buildTransactionEditMessage($transaction);
		$keyboard = $this->keyboardBuilder->buildTransactionEditKeyboard(
			$transactionId
		);

		$this->editMessageText($message, $keyboard);
		$this->answerCallbackQuery("✏️ Pilih field yang akan diedit");

		return ["success" => true];
	}

	private function handleEditField(
		Transaction $transaction,
		string $field,
		array $data
	): array {
		$value = $data["value"] ?? null;

		if ($value !== null) {
			try {
				// Update transaction field
				$updateData = [$field => $value];

				// If amount is updated, need to adjust account balance
				if ($field === "amount") {
					$oldAmount = $transaction->amount;
					$newAmount = $value;
					$difference = $newAmount - $oldAmount;

					// Update account balance
					$account = $transaction->account;
					if ($transaction->type === "income") {
						$account->balance = $account->balance->plus($difference);
					} else {
						$account->balance = $account->balance->minus($difference);
					}
					$account->save();
				}

				$transaction->update($updateData);

				$message = $this->messageBuilder->buildTransactionDetailMessage(
					$transaction->fresh()
				);
				$keyboard = $this->keyboardBuilder->buildTransactionDetailKeyboard(
					$transaction
				);

				$this->editMessageText($message, $keyboard);
				$this->answerCallbackQuery("✅ Transaksi berhasil diperbarui");

				return ["success" => true];
			} catch (\Exception $e) {
				Log::error("Failed to edit transaction", [
					"transaction_id" => $transaction->id,
					"field" => $field,
					"error" => $e->getMessage(),
				]);

				$this->answerCallbackQuery("❌ Gagal memperbarui transaksi", true);
				return ["success" => false];
			}
		}

		// Show input for field
		$message = $this->messageBuilder->buildTransactionEditFieldMessage(
			$transaction,
			$field
		);
		$keyboard = $this->keyboardBuilder->buildTransactionEditFieldKeyboard(
			$transactionId,
			$field
		);

		$this->editMessageText($message, $keyboard);
		$this->answerCallbackQuery("✏️ Masukkan nilai baru");

		return ["success" => true];
	}

	private function handleDelete(string $transactionId, array $data): array
	{
		$transaction = $this->validateUserOwnership(
			Transaction::class,
			$transactionId
		);
		if (!$transaction) {
			return ["success" => false];
		}

		$confirm = $data["confirm"] ?? false;

		if ($confirm) {
			try {
				// Adjust account balance before deleting
				$account = $transaction->account;
				if ($transaction->type === "income") {
					$account->balance = $account->balance->minus($transaction->amount);
				} else {
					$account->balance = $account->balance->plus($transaction->amount);
				}
				$account->save();

				// Delete transaction
				$transaction->delete();

				$this->deleteMessage();
				$this->answerCallbackQuery("🗑️ Transaksi berhasil dihapus");

				// Optionally send summary message
				$summaryMessage = "✅ *Transaksi Dihapus*\n\n";
				$summaryMessage .= "Transaksi berhasil dihapus dari sistem.\n";
				$summaryMessage .=
					"Saldo akun {$account->name}: Rp " .
					number_format($account->balance->getAmount(), 0, ",", ".");

				$this->sendMessage($summaryMessage);

				return ["success" => true];
			} catch (\Exception $e) {
				Log::error("Failed to delete transaction", [
					"transaction_id" => $transaction->id,
					"error" => $e->getMessage(),
				]);

				$this->answerCallbackQuery("❌ Gagal menghapus transaksi", true);
				return ["success" => false];
			}
		}

		// Show confirmation with keyboard options
		$message = "⚠️ *Konfirmasi Penghapusan*\n\n";
		$message .= "Anda yakin ingin menghapus transaksi ini?\n\n";
		$message .= "**Deskripsi:** {$transaction->description}\n";
		$message .=
			"**Jumlah:** Rp " .
			number_format($transaction->amount, 0, ",", ".") .
			"\n";
		$message .=
			"**Tanggal:** " .
			$transaction->transaction_date->format("d/m/Y") .
			"\n\n";
		$message .= "Tindakan ini tidak dapat dibatalkan!";

		$keyboard = [
			"inline_keyboard" => [
				[
					[
						"text" => "✅ Ya, Hapus",
						"callback_data" => json_encode([
							"action" => "delete",
							"type" => "transaction",
							"id" => $transactionId,
							"confirm" => true,
						]),
					],
					[
						"text" => "❌ Batalkan",
						"callback_data" => json_encode([
							"action" => "view",
							"type" => "transaction",
							"id" => $transactionId,
						]),
					],
				],
			],
		];

		$this->editMessageText($message, $keyboard);
		$this->answerCallbackQuery("⚠️ Konfirmasi penghapusan");

		return ["success" => true];
	}

	private function handleDuplicate(string $transactionId, array $data): array
	{
		$transaction = $this->validateUserOwnership(
			Transaction::class,
			$transactionId
		);
		if (!$transaction) {
			return ["success" => false];
		}

		try {
			// Duplicate transaction
			$newTransaction = $transaction->replicate();
			$newTransaction->transaction_date = now();
			$newTransaction->description = "(Duplikat) " . $transaction->description;
			$newTransaction->save();

			// Update account balance
			$account = $transaction->account;
			if ($newTransaction->type === "income") {
				$account->balance = $account->balance->plus($newTransaction->amount);
			} else {
				$account->balance = $account->balance->minus($newTransaction->amount);
			}
			$account->save();

			$message = $this->messageBuilder->buildTransactionDetailMessage(
				$newTransaction
			);
			$keyboard = $this->keyboardBuilder->buildTransactionDetailKeyboard(
				$newTransaction
			);

			$this->editMessageText($message, $keyboard);
			$this->answerCallbackQuery("✅ Transaksi berhasil diduplikasi");

			return ["success" => true];
		} catch (\Exception $e) {
			Log::error("Failed to duplicate transaction", [
				"transaction_id" => $transaction->id,
				"error" => $e->getMessage(),
			]);

			$this->answerCallbackQuery("❌ Gagal menduplikasi transaksi", true);
			return ["success" => false];
		}
	}

	private function handleViewCategory(string $transactionId, array $data): array
	{
		$categoryId = $data["id"] ?? null;

		if (!$categoryId) {
			$this->answerCallbackQuery("❌ Kategori tidak ditemukan", true);
			return ["success" => false];
		}

		try {
			// Get category with user validation
			$category = Category::where("id", $categoryId)
				->where("user_id", $this->user->id)
				->first();

			if (!$category) {
				$this->answerCallbackQuery("❌ Kategori tidak ditemukan", true);
				return ["success" => false];
			}

			// Build category detail message
			$message = "📊 *Detail Kategori*\n\n";
			$message .= "**Nama:** {$category->name}\n";
			$message .=
				"**Tipe:** " .
				($category->type === "income" ? "Pemasukan" : "Pengeluaran") .
				"\n";

			if ($category->description) {
				$message .= "**Deskripsi:** {$category->description}\n";
			}

			if ($category->budget_limit) {
				$message .=
					"**Batas Anggaran:** Rp " .
					number_format($category->budget_limit, 0, ",", ".") .
					"\n";
			}

			// Get recent transactions in this category
			$recentTransactions = Transaction::where("category_id", $categoryId)
				->where("user_id", $this->user->id)
				->orderBy("transaction_date", "desc")
				->limit(5)
				->get();

			if ($recentTransactions->isNotEmpty()) {
				$message .= "\n**Transaksi Terbaru:**\n";
				foreach ($recentTransactions as $transaction) {
					$amount = number_format($transaction->amount, 0, ",", ".");
					$date = $transaction->transaction_date->format("d/m");
					$message .= "• {$date}: Rp {$amount} - {$transaction->description}\n";
				}
			}

			// Build keyboard to go back
			$keyboard = [
				"inline_keyboard" => [
					[
						[
							"text" => "🔙 Kembali ke Transaksi",
							"callback_data" => json_encode([
								"action" => "view",
								"type" => "transaction",
								"id" => $transactionId,
							]),
						],
					],
				],
			];

			$this->editMessageText($message, $keyboard);
			$this->answerCallbackQuery("📊 Memuat detail kategori...");

			return ["success" => true];
		} catch (\Exception $e) {
			Log::error("Failed to view category", [
				"category_id" => $categoryId,
				"error" => $e->getMessage(),
			]);

			$this->answerCallbackQuery("❌ Gagal memuat kategori", true);
			return ["success" => false];
		}
	}

	private function handleCancel(string $transactionId, array $data): array
	{
		// Hapus pesan transaksi
		$this->deleteMessage();
		$this->answerCallbackQuery("Pesan ditutup");

		return ["success" => true];
	}

	// Helper method untuk menampilkan form edit dengan keyboard yang sesuai
	private function showEditForm(Transaction $transaction): array
	{
		$message = "✏️ *Edit Transaksi*\n\n";
		$message .= "Pilih field yang ingin diedit:\n\n";

		$message .= "1. **Deskripsi**: {$transaction->description}\n";
		$message .=
			"2. **Jumlah**: Rp " .
			number_format($transaction->amount, 0, ",", ".") .
			"\n";
		$message .=
			"3. **Tanggal**: " .
			$transaction->transaction_date->format("d/m/Y") .
			"\n";
		$message .= "4. **Kategori**: {$transaction->category->name}\n";

		if ($transaction->notes) {
			$message .= "5. **Catatan**: {$transaction->notes}\n";
		} else {
			$message .= "5. **Catatan**: (Belum ada)\n";
		}

		$keyboard = [
			"inline_keyboard" => [
				[
					[
						"text" => "1. Deskripsi",
						"callback_data" => json_encode([
							"action" => "edit",
							"type" => "transaction",
							"id" => $transaction->id,
							"field" => "description",
						]),
					],
				],
				[
					[
						"text" => "2. Jumlah",
						"callback_data" => json_encode([
							"action" => "edit",
							"type" => "transaction",
							"id" => $transaction->id,
							"field" => "amount",
						]),
					],
				],
				[
					[
						"text" => "3. Tanggal",
						"callback_data" => json_encode([
							"action" => "edit",
							"type" => "transaction",
							"id" => $transaction->id,
							"field" => "transaction_date",
						]),
					],
				],
				[
					[
						"text" => "4. Kategori",
						"callback_data" => json_encode([
							"action" => "edit",
							"type" => "transaction",
							"id" => $transaction->id,
							"field" => "category_id",
						]),
					],
				],
				[
					[
						"text" => "5. Catatan",
						"callback_data" => json_encode([
							"action" => "edit",
							"type" => "transaction",
							"id" => $transaction->id,
							"field" => "notes",
						]),
					],
				],
				[
					[
						"text" => "🔙 Kembali",
						"callback_data" => json_encode([
							"action" => "view",
							"type" => "transaction",
							"id" => $transaction->id,
						]),
					],
				],
			],
		];

		$this->editMessageText($message, $keyboard);
		$this->answerCallbackQuery("Pilih field untuk diedit");

		return ["success" => true];
	}
}
