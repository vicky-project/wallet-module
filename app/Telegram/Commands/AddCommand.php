<?php
namespace Modules\Wallet\Telegram\Commands;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Modules\Wallet\Enums\CategoryType;
use Modules\Wallet\Services\AccountService;
use Modules\Wallet\Services\CategoryService;
use Modules\Wallet\Services\TransactionService;
use Modules\Telegram\Services\Handlers\Commands\BaseCommandHandler;
use Modules\Telegram\Services\Support\TelegramApi;
use Modules\Telegram\Services\TelegramService;
use Modules\Wallet\Telegram\Builders\MessageBuilder;

class AddCommand extends BaseCommandHandler
{
	protected TelegramService $service;
	protected MessageBuilder $builder;
	protected TransactionService $transactionService;

	public function __construct(TelegramApi $telegram, TelegramService $service)
	{
		parent::__construct($telegram);
		$this->service = $service;
		$this->builder = app(MessageBuilder::class);
		$this->transactionService = app(TransactionService::class);
	}

	public function getName(): string
	{
		return "add";
	}

	public function getDescription(): string
	{
		return "Add transaction";
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

			// Parse command: /add <type> <amount> <description> [#category] [@account]
			$parts = explode(" ", $text, 5);

			if (count($parts) < 4) {
				return [
					"status" => "add_transaction_failed",
					"send_message" => [
						"text" => $this->getAddCommandUsage(),
						"parse_mode" => "MarkdownV2",
					],
				];
			}

			return $this->processAdd($chatId, $user, $text, $parts);
		} catch (\Exception $e) {
			Log::error("Failed to add transaction.", [
				"message" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
			]);

			return [
				"status" => "add_transaction_failed",
				"message" => $e->getMessage(),
				"send_message" => ["text" => $this->getErrorAnswer($e->getMessage())],
			];
		}
	}

	private function processAdd(
		int $chatId,
		User $user,
		string $text,
		array $parts
	): array {
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
				"category_id" => $this->getCategoryId(
					$chatId,
					$user,
					$categoryName,
					$type
				),
				"account_id" => $this->getAccountId($chatId, $user, $accountName),
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
					"send_message" => [
						"text" => $this->formatSuccessMessage(
							$result,
							$amount,
							$description,
							$categoryName,
							$accountName
						),
						"parse_mode" => "MarkdownV2",
					],
				];
			} else {
				return [
					"success" => false,
					"send_message" => ["text" => "❌ Gagal: " . $result["message"]],
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
	 * Get usage instructions for /add command
	 */
	private function getAddCommandUsage(): string
	{
		$message = "❌ *Format salah!*\n\n";

		return $message . $this->builder->buildAddCommandUsage();
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

	private function getCategoryId(
		int $chatId,
		User $user,
		string $categoryName,
		string $type
	): int {
		$categoryName = str($categoryName)->replace("_", " ");
		$categoryService = app(CategoryService::class);
		$category = $categoryService->searchCategories($user, $categoryName);

		if ($category->isEmpty()) {
			$message = "Category {$categoryName} is not exists in your categories. Try to create new category for {$categoryName}";
			$this->sendMessage($chatId, $message);

			$category = $categoryService->createCategory($user, [
				"name" => $categoryName,
				"type" => in_array($type, CategoryType::cases())
					? $type
					: CategoryType::EXPENSE,
			]);
		}

		if ($category instanceof Collection) {
			if ($category->count() === 0) {
				throw new \RuntimeException(
					"Category not found and Failed to create new Category"
				);
			}

			$category = $category->first();
		}

		return $category->id;
	}

	private function getAccountId(
		int $chatId,
		User $user,
		string $accountName
	): int {
		$accountName = str($accountName)->replace("_", " ");
		$accountService = app(AccountService::class);
		$account = $accountService
			->getRepository()
			->getUserAccounts($user, ["search" => $accountName]);

		if ($account->isEmpty()) {
			$message = "Account {$accountName} is not exists in your accounts. Try to create new account for {$accountName}";

			$this->sendMessage($chatId, $message);

			$account = $accountService->createAccount($user, [
				"name" => $accountName,
			]);
		}

		if ($account instanceof Collection) {
			if ($account->count() === 0) {
				throw new \RuntimeException(
					"Account not found and can not create new Account"
				);
			}

			$account = $account->first();
		}

		return $account->id;
	}

	private function formatSuccessMessage(
		array $result,
		int $amount,
		string $description,
		string $categoryName,
		string $accountName
	): string {
		return "✅️ Transaksi baru berhasil di tambahkan.\n\n" .
			"● 💰 {$amount}\n" .
			"● 📃 {$description}\n" .
			"● 📫 {$categoryName}\n" .
			"● 🏦 {$accountName}";
	}
}
