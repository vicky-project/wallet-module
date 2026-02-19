<?php
namespace Modules\Wallet\Services\Telegram;

use App\Models\User;
use Modules\Wallet\Services\TransactionService;
use Modules\Wallet\Services\Telegram\Builders\MessageBuilder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CommandService
{
	protected $transactionService;
	protected $messageBuilder;

	public function __construct(
		TransactionService $transactionService,
		MessageBuilder $messageBuilder
	) {
		$this->transactionService = $transactionService;
		$this->messageBuilder = $messageBuilder;
	}

	/**
	 * Process /add command
	 */
	public function processAddCommand(User $user, string $text): array
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

			return [
				"success" => false,
				"message" => "❌ Error: " . $e->getMessage(),
			];
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
	 * Get category ID from name
	 */
	private function getCategoryId(User $user, string $name): int
	{
		$category = $user
			->categories()
			->where("name", "like", "%{$name}%")
			->active()
			->first();

		if (!$category) {
			// Get default category
			$category = $user
				->categories()
				->where("name", "Umum")
				->first();

			if (!$category) {
				// Create default category
				$category = $user->categories()->create([
					"name" => "Umum",
					"type" => "expense",
					"slug" => Str::slug("Umum-" . $user->id),
					"is_budgetable" => false,
				]);
			}
		}

		return $category->id;
	}

	/**
	 * Get account ID from name
	 */
	private function getAccountId(User $user, string $name): int
	{
		$account = $user
			->accounts()
			->where("name", "like", "%{$name}%")
			->active()
			->first();

		if (!$account) {
			// Get default account
			$account = $user
				->accounts()
				->where("is_default", true)
				->first();

			if (!$account) {
				// Get first active account
				$account = $user
					->accounts()
					->active()
					->first();

				if (!$account) {
					throw new \Exception("Tidak ada akun yang tersedia");
				}
			}
		}

		return $account->id;
	}

	/**
	 * Format success message
	 */
	private function formatSuccessMessage(
		array $result,
		int $amount,
		string $description,
		string $categoryName,
		string $accountName
	): string {
		$formattedAmount = number_format($amount);

		$message = "✅ *Transaksi Berhasil!*\n\n";
		$message .= "📊 *Deskripsi:* {$description}\n";
		$message .= "💰 *Jumlah:* Rp {$formattedAmount}\n";
		$message .= "📂 *Kategori:* #{$categoryName}\n";
		$message .= "🏦 *Akun:* @{$accountName}\n";
		$message .= "📅 *Tanggal:* " . now()->format("d/m/Y H:i") . "\n\n";
		$message .= "ID: `{$result["transaction"]->uuid}`";

		return $message;
	}

	/**
	 * Get usage instructions for /add command
	 */
	private function getAddCommandUsage(): string
	{
		$message = "❌ *Format salah!*\n\n";

		return $message . $this->messageBuilder->buildAddCommandUsage();
	}
}
