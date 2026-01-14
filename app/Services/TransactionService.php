<?php

namespace Modules\Wallet\Services;

use App\Models\User;
use Modules\Wallet\Enums\TransactionType;
use Modules\Wallet\Repositories\TransactionRepository;
use Modules\Wallet\Repositories\AccountRepository;
use Modules\Wallet\Repositories\CategoryRepository;
use Modules\Wallet\Repositories\BudgetRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TransactionService
{
	protected $transactionRepository;
	protected $accountRepository;
	protected $categoryRepository;
	protected $budgetRepository;

	public function __construct(
		TransactionRepository $transactionRepository,
		AccountRepository $accountRepository,
		CategoryRepository $categoryRepository,
		BudgetRepository $budgetRepository
	) {
		$this->transactionRepository = $transactionRepository;
		$this->accountRepository = $accountRepository;
		$this->categoryRepository = $categoryRepository;
		$this->budgetRepository = $budgetRepository;
	}

	/**
	 * Create a new transaction
	 */
	public function createTransaction(array $data, User $user): array
	{
		try {
			// Validate account
			$account = $this->accountRepository->find($data["account_id"]);
			if (!$account || $account->user_id != $user->id || !$account->is_active) {
				throw new \Exception("Akun tidak ditemukan atau tidak aktif.");
			}

			// Validate category
			$category = $this->categoryRepository->find($data["category_id"]);
			if (!$category || !$category->is_active) {
				throw new \Exception("Kategori tidak ditemukan atau tidak aktif.");
			}

			// For transfer, validate to_account
			if ($data["type"] === TransactionType::TRANSFER->value) {
				$toAccount = $this->accountRepository->find($data["to_account_id"]);
				if (
					!$toAccount ||
					$toAccount->user_id != $user->id ||
					!$toAccount->is_active
				) {
					throw new \Exception("Akun tujuan tidak ditemukan atau tidak aktif.");
				}

				if ($data["account_id"] == $data["to_account_id"]) {
					throw new \Exception("Tidak dapat transfer ke akun yang sama.");
				}
			}

			// Check budget for expense
			if (
				$data["type"] === TransactionType::EXPENSE->value &&
				$category->is_budgetable
			) {
				$this->checkBudgetLimit($data, $user);
			}

			// Add user_id
			$data["user_id"] = $user->id;

			// Create transaction
			$transaction = $this->transactionRepository->createTransaction($data);

			return [
				"success" => true,
				"transaction" => $transaction,
				"message" => "Transaksi berhasil ditambahkan.",
			];
		} catch (\Exception $e) {
			logger()->error("Failed create transaction data", [
				"message" => $e->getMessage(),
				"trace" => $e->getTrace(),
			]);
			return [
				"success" => false,
				"message" => $e->getMessage(),
			];
		}
	}

	/**
	 * Update an existing transaction
	 */
	public function updateTransaction(
		string $uuid,
		array $data,
		User $user
	): array {
		try {
			$transaction = $this->transactionRepository->getByUuid($uuid);

			if (!$transaction) {
				throw new \Exception("Transaksi tidak ditemukan.");
			}

			if ($transaction->user_id != $user->id) {
				throw new \Exception("Anda tidak memiliki akses ke transaksi ini.");
			}

			// Validate account if changed
			if (isset($data["account_id"])) {
				$account = $this->accountRepository->find($data["account_id"]);
				if (
					!$account ||
					$account->user_id != $user->id ||
					!$account->is_active
				) {
					throw new \Exception("Akun tidak ditemukan atau tidak aktif.");
				}
			}

			// Validate category if changed
			if (isset($data["category_id"])) {
				$category = $this->categoryRepository->find($data["category_id"]);
				if (!$category || !$category->is_active) {
					throw new \Exception("Kategori tidak ditemukan atau tidak aktif.");
				}
			}

			// Update transaction
			$updatedTransaction = $this->transactionRepository->updateTransaction(
				$transaction,
				$data
			);

			return [
				"success" => true,
				"transaction" => $updatedTransaction,
				"message" => "Transaksi berhasil diperbarui.",
			];
		} catch (\Exception $e) {
			return [
				"success" => false,
				"message" => $e->getMessage(),
			];
		}
	}

	/**
	 * Delete a transaction
	 */
	public function deleteTransaction(string $uuid, User $user): array
	{
		try {
			$transaction = $this->transactionRepository->getByUuid($uuid);

			if (!$transaction) {
				throw new \Exception("Transaksi tidak ditemukan.");
			}

			if ($transaction->user_id != $user->id) {
				throw new \Exception("Anda tidak memiliki akses ke transaksi ini.");
			}

			$deleted = $this->transactionRepository->deleteTransaction($transaction);

			if ($deleted) {
				return [
					"success" => true,
					"message" => "Transaksi berhasil dihapus.",
				];
			} else {
				throw new \Exception("Gagal menghapus transaksi.");
			}
		} catch (\Exception $e) {
			return [
				"success" => false,
				"message" => $e->getMessage(),
			];
		}
	}

	/**
	 * Get transaction by UUID
	 */
	public function getTransaction(string $uuid, User $user): array
	{
		try {
			$transaction = $this->transactionRepository->getByUuid($uuid);

			if (!$transaction) {
				throw new \Exception("Transaksi tidak ditemukan.");
			}

			if ($transaction->user_id != $user->id) {
				throw new \Exception("Anda tidak memiliki akses ke transaksi ini.");
			}

			return [
				"success" => true,
				"transaction" => $transaction,
			];
		} catch (\Exception $e) {
			return [
				"success" => false,
				"message" => $e->getMessage(),
			];
		}
	}

	/**
	 * Get paginated transactions with filters
	 */
	public function getPaginatedTransactions(
		array $filters = [],
		int $perPage = 20
	): array {
		try {
			$paginator = $this->transactionRepository->getPaginatedTransactions(
				$filters,
				$perPage
			);

			// Calculate totals
			$totalIncome = $this->getTotalByType("income", $filters);
			$totalExpense = $this->getTotalByType("expense", $filters);
			$totalTransfer = $this->getTotalByType("transfer", $filters);

			return [
				"success" => true,
				"transactions" => $paginator,
				"totals" => [
					"income" => $totalIncome,
					"expense" => $totalExpense,
					"transfer" => $totalTransfer,
					"net" => $totalIncome - $totalExpense,
				],
			];
		} catch (\Exception $e) {
			return [
				"success" => false,
				"message" => $e->getMessage(),
			];
		}
	}

	/**
	 * Get dashboard summary
	 */
	public function getDashboardSummary(User $user): array
	{
		try {
			$summary = $this->transactionRepository->getDashboardSummary($user);

			// Get account summary
			$accountSummary = $this->accountRepository->getAccountSummary($user);

			// Get category stats
			$categoryStats = $this->categoryRepository->getCategoryStats($user);

			// Get budget warnings
			$budgetWarnings = $this->categoryRepository->getBudgetWarnings($user);

			return [
				"success" => true,
				"transaction_summary" => $summary,
				"account_summary" => $accountSummary,
				"category_stats" => $categoryStats,
				"budget_warnings" => $budgetWarnings,
			];
		} catch (\Exception $e) {
			return [
				"success" => false,
				"message" => $e->getMessage(),
			];
		}
	}

	/**
	 * Get transaction statistics
	 */
	public function getTransactionStats(User $user): array
	{
		try {
			$stats = $this->transactionRepository->getTransactionStats($user->id);

			// Get monthly trends
			$monthlyTrends = $this->transactionRepository->getMonthlyTrends(
				$user->id,
				6
			);

			// Get daily totals for current month
			$now = Carbon::now();
			$dailyTotals = $this->transactionRepository->getDailyTotals(
				$user->id,
				$now->startOfMonth()->format("Y-m-d"),
				$now->endOfMonth()->format("Y-m-d")
			);

			return [
				"success" => true,
				"stats" => $stats,
				"monthly_trends" => $monthlyTrends,
				"daily_totals" => $dailyTotals,
			];
		} catch (\Exception $e) {
			return [
				"success" => false,
				"message" => $e->getMessage(),
			];
		}
	}

	/**
	 * Export transactions
	 */
	public function exportTransactions(
		User $user,
		string $format = "excel",
		string $startDate = null,
		string $endDate = null
	): array {
		try {
			$transactions = $this->transactionRepository->getForExport(
				$user->id,
				$startDate,
				$endDate
			);

			if ($transactions->isEmpty()) {
				throw new \Exception("Tidak ada data transaksi untuk diekspor.");
			}

			// Format data based on export type
			$formattedData = $this->formatExportData($transactions, $format);

			return [
				"success" => true,
				"data" => $formattedData,
				"count" => $transactions->count(),
				"period" => [
					"start" => $startDate,
					"end" => $endDate,
				],
			];
		} catch (\Exception $e) {
			return [
				"success" => false,
				"message" => $e->getMessage(),
			];
		}
	}

	/**
	 * Import transactions
	 */
	public function importTransactions(
		array $data,
		User $user,
		string $format = "excel"
	): array {
		try {
			// Parse data based on format
			$parsedData = $this->parseImportData($data, $format);

			if (empty($parsedData)) {
				throw new \Exception("Data import kosong atau format tidak sesuai.");
			}

			// Import transactions
			$results = $this->transactionRepository->importTransactions(
				$parsedData,
				$user
			);

			return [
				"success" => true,
				"results" => $results,
				"message" => sprintf(
					"Import selesai. Berhasil: %d, Gagal: %d",
					$results["success"],
					$results["failed"]
				),
			];
		} catch (\Exception $e) {
			return [
				"success" => false,
				"message" => $e->getMessage(),
			];
		}
	}

	/**
	 * Check budget limit for expense
	 */
	private function checkBudgetLimit(array $data, User $user): void
	{
		$category = $this->categoryRepository->find($data["category_id"]);

		if (!$category || !$category->is_budgetable) {
			return;
		}

		$transactionDate = $data["transaction_date"] ?? now();
		$budget = $this->budgetRepository->getActiveBudgetForDate(
			$category,
			$user->id,
			Carbon::parse($transactionDate)
		);

		if ($budget) {
			$projectedSpent = $budget->spent + $data["amount"];
			if ($projectedSpent > $budget->amount) {
				// Log warning but don't prevent transaction
				\Log::warning("Budget exceeded", [
					"budget_id" => $budget->id,
					"category_id" => $data["category_id"],
					"amount" => $data["amount"],
					"budget_limit" => $budget->amount,
					"current_spent" => $budget->spent,
					"user_id" => $user->id,
				]);
			}
		}
	}

	/**
	 * Get total by transaction type
	 */
	private function getTotalByType(string $type, array $filters): int
	{
		$user = auth()->user();
		$query = $this->transactionRepository
			->query()
			->where("user_id", $user->id)
			->where("type", $type);

		if (isset($filters["start_date"])) {
			$query->whereDate("transaction_date", ">=", $filters["start_date"]);
		}

		if (isset($filters["end_date"])) {
			$query->whereDate("transaction_date", "<=", $filters["end_date"]);
		}

		if (isset($filters["account_id"])) {
			$query->where("account_id", $filters["account_id"]);
		}

		if (isset($filters["category_id"])) {
			$query->where("category_id", $filters["category_id"]);
		}

		return $query->sum("amount") ?? 0;
	}

	/**
	 * Format export data
	 */
	private function formatExportData(
		Collection $transactions,
		string $format
	): array {
		if ($format === "json") {
			return $transactions->toArray();
		}

		// Default: array for CSV/Excel
		return $transactions
			->map(function ($transaction) {
				return [
					"Tanggal" => $transaction["Tanggal"],
					"Waktu" => $transaction["Waktu"],
					"Tipe" => $transaction["Tipe"],
					"Deskripsi" => $transaction["Deskripsi"],
					"Kategori" => $transaction["Kategori"],
					"Akun" => $transaction["Akun"],
					"Akun Tujuan" => $transaction["Akun Tujuan"],
					"Jumlah" => number_format($transaction["Jumlah"], 0, ",", "."),
					"Catatan" => $transaction["Catatan"],
					"Metode Pembayaran" => $transaction["Metode Pembayaran"],
					"Nomor Referensi" => $transaction["Nomor Referensi"],
				];
			})
			->toArray();
	}

	/**
	 * Parse import data
	 */
	private function parseImportData(array $data, string $format): array
	{
		$parsedData = [];

		if ($format === "json") {
			return $data;
		}

		// Parse CSV/Excel data
		foreach ($data as $row) {
			// Map columns based on expected format
			$transaction = [
				"account_id" => $this->mapAccountName($row["Akun"] ?? $row["account"]),
				"category_id" => $this->mapCategoryName(
					$row["Kategori"] ?? $row["category"]
				),
				"type" => $this->mapTransactionType($row["Tipe"] ?? $row["type"]),
				"amount" => $this->parseAmount($row["Jumlah"] ?? $row["amount"]),
				"description" => $row["Deskripsi"] ?? $row["description"],
				"transaction_date" => $this->parseDate(
					$row["Tanggal"] ?? $row["date"],
					$row["Waktu"] ?? $row["time"]
				),
				"notes" => $row["Catatan"] ?? ($row["notes"] ?? ""),
				"payment_method" =>
					$row["Metode Pembayaran"] ?? ($row["payment_method"] ?? ""),
				"reference_number" =>
					$row["Nomor Referensi"] ?? ($row["reference_number"] ?? ""),
			];

			// For transfers, map to_account
			if ($transaction["type"] === "transfer") {
				$transaction["to_account_id"] = $this->mapAccountName(
					$row["Akun Tujuan"] ?? $row["to_account"]
				);
			}

			$parsedData[] = $transaction;
		}

		return $parsedData;
	}

	/**
	 * Map account name to ID
	 */
	private function mapAccountName(string $accountName): int
	{
		$user = auth()->user();
		$account = $this->accountRepository
			->getUserAccounts($user)
			->firstWhere("name", $accountName);

		if (!$account) {
			throw new \Exception("Akun '{$accountName}' tidak ditemukan.");
		}

		return $account->id;
	}

	/**
	 * Map category name to ID
	 */
	private function mapCategoryName(string $categoryName): int
	{
		$user = auth()->user();
		$category = $this->categoryRepository
			->getUserCategories()
			->firstWhere("name", $categoryName);

		if (!$category) {
			throw new \Exception("Kategori '{$categoryName}' tidak ditemukan.");
		}

		return $category->id;
	}

	/**
	 * Map transaction type
	 */
	private function mapTransactionType(string $type): string
	{
		$typeMap = [
			"pemasukan" => "income",
			"pendapatan" => "income",
			"income" => "income",
			"pengeluaran" => "expense",
			"expense" => "expense",
			"transfer" => "transfer",
		];

		$type = strtolower(trim($type));

		return $typeMap[$type] ?? $type;
	}

	/**
	 * Parse amount
	 */
	private function parseAmount($amount): int
	{
		if (is_numeric($amount)) {
			return (int) $amount;
		}

		// Remove currency symbols and thousands separators
		$amount = preg_replace("/[^\d,\.]/", "", $amount);
		$amount = str_replace(".", "", $amount);
		$amount = str_replace(",", ".", $amount);

		return (int) round(floatval($amount));
	}

	/**
	 * Parse date
	 */
	private function parseDate($date, $time = null): string
	{
		try {
			if ($time) {
				return Carbon::parse("{$date} {$time}")->format("Y-m-d H:i:s");
			}

			return Carbon::parse($date)->format("Y-m-d H:i:s");
		} catch (\Exception $e) {
			return now()->format("Y-m-d H:i:s");
		}
	}

	/**
	 * Bulk update transactions
	 */
	public function bulkUpdate(array $ids, array $data, User $user): array
	{
		try {
			// Verify all transactions belong to user
			$transactions = $this->transactionRepository
				->query()
				->where("user_id", $user->id)
				->whereIn("id", $ids)
				->count();

			if ($transactions !== count($ids)) {
				throw new \Exception(
					"Beberapa transaksi tidak ditemukan atau tidak dapat diakses."
				);
			}

			// Perform bulk update
			$updated = $this->transactionRepository->bulkUpdate($ids, $data);

			return [
				"success" => true,
				"updated" => $updated,
				"message" => "{$updated} transaksi berhasil diperbarui.",
			];
		} catch (\Exception $e) {
			return [
				"success" => false,
				"message" => $e->getMessage(),
			];
		}
	}

	/**
	 * Bulk delete transactions
	 */
	public function bulkDelete(array $ids, User $user): array
	{
		try {
			// Verify all transactions belong to user
			$transactions = $this->transactionRepository
				->query()
				->where("user_id", $user->id)
				->whereIn("id", $ids)
				->count();

			if ($transactions !== count($ids)) {
				throw new \Exception(
					"Beberapa transaksi tidak ditemukan atau tidak dapat diakses."
				);
			}

			// Perform bulk delete
			$deleted = $this->transactionRepository->bulkDelete($ids);

			return [
				"success" => true,
				"deleted" => $deleted,
				"message" => "{$deleted} transaksi berhasil dihapus.",
			];
		} catch (\Exception $e) {
			return [
				"success" => false,
				"message" => $e->getMessage(),
			];
		}
	}

	/**
	 * Duplicate transaction
	 */
	public function duplicateTransaction(string $uuid, User $user): array
	{
		try {
			$transaction = $this->transactionRepository->getByUuid($uuid);

			if (!$transaction) {
				throw new \Exception("Transaksi tidak ditemukan.");
			}

			if ($transaction->user_id != $user->id) {
				throw new \Exception("Anda tidak memiliki akses ke transaksi ini.");
			}

			$duplicated = $this->transactionRepository->duplicateTransaction(
				$transaction
			);

			return [
				"success" => true,
				"transaction" => $duplicated,
				"message" => "Transaksi berhasil diduplikasi.",
			];
		} catch (\Exception $e) {
			return [
				"success" => false,
				"message" => $e->getMessage(),
			];
		}
	}

	/**
	 * Search transactions with advanced filters
	 */
	public function searchAdvanced(array $filters): array
	{
		try {
			$transactions = $this->transactionRepository->searchAdvanced($filters);

			// Calculate summary
			$summary = [
				"total" => $transactions->count(),
				"total_amount" => $transactions->sum("amount"),
				"total_income" => $transactions->where("type", "income")->sum("amount"),
				"total_expense" => $transactions
					->where("type", "expense")
					->sum("amount"),
				"total_transfer" => $transactions
					->where("type", "transfer")
					->sum("amount"),
			];

			return [
				"success" => true,
				"transactions" => $transactions,
				"summary" => $summary,
				"count" => $transactions->count(),
			];
		} catch (\Exception $e) {
			return [
				"success" => false,
				"message" => $e->getMessage(),
			];
		}
	}

	/**
	 * Get daily summary
	 */
	public function getDailySummary(
		User $user,
		string $startDate,
		string $endDate
	): array {
		try {
			$dailySummary = $this->transactionRepository->getDailySummary(
				$user->id,
				$startDate,
				$endDate
			);

			return [
				"success" => true,
				"daily_summary" => $dailySummary,
				"period" => [
					"start" => $startDate,
					"end" => $endDate,
				],
			];
		} catch (\Exception $e) {
			return [
				"success" => false,
				"message" => $e->getMessage(),
			];
		}
	}

	/**
	 * Get transaction analytics
	 */
	public function getAnalytics(User $user, string $period = "monthly"): array
	{
		try {
			$now = Carbon::now();

			switch ($period) {
				case "weekly":
					$startDate = $now->copy()->startOfWeek();
					$endDate = $now->copy()->endOfWeek();
					break;
				case "monthly":
					$startDate = $now->copy()->startOfMonth();
					$endDate = $now->copy()->endOfMonth();
					break;
				case "yearly":
					$startDate = $now->copy()->startOfYear();
					$endDate = $now->copy()->endOfYear();
					break;
				default:
					$startDate = $now->copy()->subDays(30);
					$endDate = $now->copy();
			}

			// Get summary by type
			$summary = $this->transactionRepository->getSummaryByType(
				$user->id,
				$startDate->format("Y-m-d"),
				$endDate->format("Y-m-d")
			);

			// Get category spending
			$categorySpending = $this->transactionRepository->getCategorySpending(
				$user->id,
				$startDate->format("Y-m-d"),
				$endDate->format("Y-m-d")
			);

			// Get daily totals
			$dailyTotals = $this->transactionRepository->getDailyTotals(
				$user->id,
				$startDate->format("Y-m-d"),
				$endDate->format("Y-m-d")
			);

			return [
				"success" => true,
				"analytics" => [
					"summary" => $summary,
					"category_spending" => $categorySpending,
					"daily_totals" => $dailyTotals,
					"period" => [
						"type" => $period,
						"start" => $startDate,
						"end" => $endDate,
					],
				],
			];
		} catch (\Exception $e) {
			return [
				"success" => false,
				"message" => $e->getMessage(),
			];
		}
	}
}
