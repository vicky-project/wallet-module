<?php
namespace Modules\Wallet\Telegram\Commands;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Modules\Wallet\Enums\CategoryType;
use Modules\Wallet\Enums\TransactionType;
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

			return $this->processAdd($chatId, $user, $text);
		} catch (\Exception $e) {
			Log::error("Failed to add transaction.", [
				"message" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
			]);

			$this->sendMessage(
				$chatId,
				"Failed to add transaction.\n\n" . $e->getMessage()
			);

			return [
				"status" => "add_transaction_failed",
				"message" => $e->getMessage(),
				"send_message" => ["text" => $this->getErrorAnswer($e->getMessage())],
			];
		}
	}

	private function processAdd(int $chatId, User $user, string $text): array
	{
		// Parse command: /add <type> <amount> <description> [#category] [@account]

		$pattern = '/^\/add\s+(\w+)\s+(-?\d+)\s+(.+)$/';
		if (!preg_match($pattern, $text, $matches)) {
			return [
				"success" => false,
				"send_message" => [
					"text" => $this->getAddCommandUsage(),
					"parse_mode" => "MarkdownV2",
				],
			];
		}

		$type = strtolower($matches[1]);
		$amount = $this->parseAmount($matches[2]);
		$rest = $matches[3];

		if (
			!in_array(
				$type,
				collect(TransactionType::cases())
					->map(fn($type) => $type->value)
					->toArray()
			)
		) {
			throw new \Exception(
				"Type transaksi harus " .
					collect(TransactionType::cases())
						->map(fn($type) => "`{$type->value}`")
						->join(", ", " and ")
			);
		}

		// Extract optional parameters
		preg_match("/#(\w+)/", $rest, $categoryMatch);
		preg_match("/@(\w+)/", $rest, $accountMatch);

		$categoryName = $categoryMatch[1] ?? "Umum";
		$rest = str_replace($categoryMatch[0] ?? "", "", $rest);
		$accountName = $accountMatch[1] ?? "Default";
		$rest = str_replace($accountMatch[0] ?? "", "", $rest);

		$description = trim($rest);
		if (empty($description)) {
			throw new \Exception("Description is required.");
		}

		$category = $this->getCategoryUserByName($user, $categoryName);
		if ($category->isEmpty()) {
			$categoriesUser = $this->getAvailableUserCategorie($user);

			$message =
				"Category {$categoryName} is not exists in your categories. Available categories:\n\n" .
				$categoriesUser
					->map(fn($cat) => "`{$cat->name}`")
					->whenEmpty(
						fn(Collection $collection) => $collection->push(
							"No category available."
						)
					)
					->join(", ", " and ");
			return ["success" => false, "send_message" => ["text" => $message]];
		}

		if ($category->isNotEmpty() && $category->count() > 1) {
			$category = $category->first();
		}

		// Prepare transaction data
		$transactionData = [
			"type" => $type,
			"amount" => $amount,
			"description" => $description,
			"category_id" => $category->id,
			"account_id" => $this->getAccountId($chatId, $user, $accountName),
			"transaction_date" => now()->format("Y-m-d H:i:s"),
		];

		Log::info("User {$user->name} creating new transaction.", $transactionData);

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

	private function getAvailableUserCategorie(User $user): Collection
	{
		$categoryService = app(CategoryService::class);
		return $categoryService->getUserCategories($user);
	}

	private function getCategoryUserByName(
		User $user,
		string $categoryName
	): ?Collection {
		$categoryName = str($categoryName)->replace("_", " ");
		$categoryService = app(CategoryService::class);
		return $categoryService->searchCategories($user, $categoryName);
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
