<?php
namespace Modules\Wallet\Services\Telegram\Builders;

use Modules\Wallet\Models\Budget;
use Modules\Wallet\Models\Account;
use Modules\Wallet\Models\Transaction;

class KeyboardBuilder
{
	public function buildBudgetDetailKeyboard(Budget $budget): array
	{
		$budgetId = $budget->id;

		return [
			"inline_keyboard" => [
				[
					[
						"text" => "📝 Tambah Transaksi",
						"callback_data" => json_encode([
							"action" => "add-transaction",
							"type" => "budget",
							"id" => $budgetId,
						]),
					],
					[
						"text" => "📈 Lihat Grafik",
						"callback_data" => json_encode([
							"action" => "chart",
							"type" => "budget",
							"id" => $budgetId,
						]),
					],
				],
				[
					[
						"text" => "✏️ Edit Budget",
						"callback_data" => json_encode([
							"action" => "edit",
							"type" => "budget",
							"id" => $budgetId,
						]),
					],
					[
						"text" => "🗑️ Hapus Budget",
						"callback_data" => json_encode([
							"action" => "delete",
							"type" => "budget",
							"id" => $budgetId,
						]),
					],
				],
				[
					[
						"text" => "🔄 Refresh",
						"callback_data" => json_encode([
							"action" => "refresh",
							"type" => "budget",
							"id" => $budgetId,
						]),
					],
					[
						"text" => "❌ Tutup",
						"callback_data" => json_encode(["action" => "cancel"]),
					],
				],
			],
		];
	}

	public function buildBudgetMutedKeyboard(int $budgetId): array
	{
		return [
			"inline_keyboard" => [
				[
					[
						"text" => "📊 Lihat Detail",
						"callback_data" => json_encode([
							"action" => "view",
							"type" => "budget",
							"id" => $budgetId,
						]),
					],
				],
				[
					[
						"text" => "🔔 Aktifkan Kembali",
						"callback_data" => json_encode([
							"action" => "unmute",
							"type" => "budget",
							"id" => $budgetId,
						]),
					],
				],
			],
		];
	}

	public function buildAccountDetailKeyboard(Account $account): array
	{
		$accountId = $account->id;

		return [
			"inline_keyboard" => [
				[
					[
						"text" => "📋 Lihat Transaksi",
						"callback_data" => json_encode([
							"action" => "view-transactions",
							"type" => "account",
							"id" => $accountId,
						]),
					],
					[
						"text" => "📊 Grafik",
						"callback_data" => json_encode([
							"action" => "chart",
							"type" => "account",
							"id" => $accountId,
						]),
					],
				],
				[
					[
						"text" => "💰 Tambah Dana",
						"callback_data" => json_encode([
							"action" => "add-funds",
							"type" => "account",
							"id" => $accountId,
						]),
					],
					[
						"text" => "✏️ Edit Akun",
						"callback_data" => json_encode([
							"action" => "edit",
							"type" => "account",
							"id" => $accountId,
						]),
					],
				],
				[
					[
						"text" => "🔄 Refresh",
						"callback_data" => json_encode([
							"action" => "refresh",
							"type" => "account",
							"id" => $accountId,
						]),
					],
					[
						"text" => "❌ Tutup",
						"callback_data" => json_encode(["action" => "cancel"]),
					],
				],
			],
		];
	}

	public function buildAddFundsKeyboard(int $accountId): array
	{
		return [
			"inline_keyboard" => [
				[
					[
						"text" => "💰 50.000",
						"callback_data" => json_encode([
							"action" => "add-funds",
							"type" => "account",
							"id" => $accountId,
							"amount" => 50000,
						]),
					],
					[
						"text" => "💰 100.000",
						"callback_data" => json_encode([
							"action" => "add-funds",
							"type" => "account",
							"id" => $accountId,
							"amount" => 100000,
						]),
					],
					[
						"text" => "💰 500.000",
						"callback_data" => json_encode([
							"action" => "add-funds",
							"type" => "account",
							"id" => $accountId,
							"amount" => 500000,
						]),
					],
				],
				[
					[
						"text" => "💰 1.000.000",
						"callback_data" => json_encode([
							"action" => "add-funds",
							"type" => "account",
							"id" => $accountId,
							"amount" => 1000000,
						]),
					],
					[
						"text" => "💰 5.000.000",
						"callback_data" => json_encode([
							"action" => "add-funds",
							"type" => "account",
							"id" => $accountId,
							"amount" => 5000000,
						]),
					],
					[
						"text" => "💰 Custom",
						"callback_data" => json_encode([
							"action" => "custom-funds",
							"type" => "account",
							"id" => $accountId,
						]),
					],
				],
				[
					[
						"text" => "📝 Dengan Catatan",
						"callback_data" => json_encode([
							"action" => "add-funds-note",
							"type" => "account",
							"id" => $accountId,
						]),
					],
					[
						"text" => "❌ Batal",
						"callback_data" => json_encode(["action" => "cancel"]),
					],
				],
			],
		];
	}

	public function buildTransactionDetailKeyboard(
		Transaction $transaction
	): array {
		$transactionId = $transaction->id;

		return [
			"inline_keyboard" => [
				[
					[
						"text" => "✏️ Edit",
						"callback_data" => json_encode([
							"action" => "edit",
							"type" => "transaction",
							"id" => $transactionId,
						]),
					],
					[
						"text" => "📋 Salin",
						"callback_data" => json_encode([
							"action" => "duplicate",
							"type" => "transaction",
							"id" => $transactionId,
						]),
					],
				],
				[
					[
						"text" => "🗑️ Hapus",
						"callback_data" => json_encode([
							"action" => "delete",
							"type" => "transaction",
							"id" => $transactionId,
						]),
					],
					[
						"text" => "📊 Lihat Kategori",
						"callback_data" => json_encode([
							"action" => "view-category",
							"type" => "transaction",
							"id" => $transaction->category_id,
						]),
					],
				],
				[
					[
						"text" => "🔄 Refresh",
						"callback_data" => json_encode([
							"action" => "refresh",
							"type" => "transaction",
							"id" => $transactionId,
						]),
					],
					[
						"text" => "❌ Tutup",
						"callback_data" => json_encode(["action" => "cancel"]),
					],
				],
			],
		];
	}

	public function buildTransactionDeleteConfirmKeyboard(
		int $transactionId
	): array {
		return [
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
	}
}
