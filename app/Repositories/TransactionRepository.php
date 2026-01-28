<?php

namespace Modules\Wallet\Repositories;

use Carbon\Carbon;
use App\Models\User;
use Modules\Wallet\Enums\PeriodType;
use Modules\Wallet\Enums\TransactionType;
use Modules\Wallet\Helpers\Helper;
use Modules\Wallet\Models\Transaction;
use Modules\Wallet\Models\Account;
use Modules\Wallet\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;

class TransactionRepository extends BaseRepository
{
	public function __construct(Transaction $model)
	{
		parent::__construct($model);
	}

	/**
	 * Get optimized dashboard data for transactions
	 */
	public function getDashboardData(User $user, array $params = []): array
	{
		$cacheKey = Helper::generateCacheKey("dashboard_transactions", [
			"user_id" => $user->id,
			"start_date" => $params["start_date"] ?? null,
			"end_date" => $params["end_date"] ?? null,
		]);

		return Cache::remember($cacheKey, 300, function () use ($user, $params) {
			return DB::transaction(function () use ($user, $params) {
				// Single query untuk semua summary
				$summary = DB::table("transactions as t")
					->where("t.user_id", $user->id)
					->whereBetween("t.transaction_date", [
						$params["start_date"],
						$params["end_date"],
					])
					->select(
						DB::raw(
							'SUM(CASE WHEN t.type = "income" THEN t.amount ELSE 0 END) as income'
						),
						DB::raw(
							'SUM(CASE WHEN t.type = "expense" THEN t.amount ELSE 0 END) as expense'
						),
						DB::raw(
							'COUNT(CASE WHEN t.type = "income" THEN 1 END) as income_count'
						),
						DB::raw(
							'COUNT(CASE WHEN t.type = "expense" THEN 1 END) as expense_count'
						)
					)
					->first();

				// Single query untuk stats
				$stats = $this->getTransactionStats($user, $params["now"]);

				// Recent transactions
				$recentTransactions = DB::table("transactions as t")
					->join("accounts as a", "t.account_id", "=", "a.id")
					->join("categories as c", "t.category_id", "=", "c.id")
					->where("t.user_id", $user->id)
					->select(
						"t.id",
						"t.description",
						"t.amount",
						"t.type",
						"t.transaction_date",
						"a.name as account_name",
						"c.name as category_name",
						"c.icon as category_icon"
					)
					->orderBy("t.transaction_date", "desc")
					->limit(10)
					->get()
					->map(function ($recent) {
						return [
							"account_name" => $recent->account_name,
							"category_name" => $recent->category_name,
							"category_icon" => $recent->category_icon,
							"description" => $recent->description,
							"amount" => $this->toMoney($recent->amount, isInteger: false)
								->getAmount()
								->toInt(),
							"type" => $recent->type,
							"transaction_date" => $recent->transaction_date,
						];
					})
					->toArray();

				return [
					"summary" => (array) $summary,
					"stats" => (array) $stats,
					"recent_transactions" => $recentTransactions,
				];
			});
		});
	}

	/**
	 * Get transaction stats dengan query terpisah
	 */
	protected function getTransactionStats(User $user, Carbon $now): object
	{
		$today = $now->toDateString();
		$sevenDaysAgo = $now
			->copy()
			->subDays(7)
			->toDateString();
		$thirtyDaysAgo = $now
			->copy()
			->subDays(30)
			->toDateString();
		$startOfMonth = $now
			->copy()
			->startOfMonth()
			->toDateString();
		$endOfMonth = $now
			->copy()
			->endOfMonth()
			->toDateString();

		// Query 1: Today's transactions
		$todayTotal = DB::table("transactions")
			->where("user_id", $user->id)
			->whereDate("transaction_date", $today)
			->count();

		// Query 2: Last 7 days
		$last7Days = DB::table("transactions")
			->where("user_id", $user->id)
			->whereDate("transaction_date", ">=", $sevenDaysAgo)
			->whereDate("transaction_date", "<=", $today)
			->count();

		// Query 3: Last 30 days
		$last30Days = DB::table("transactions")
			->where("user_id", $user->id)
			->whereDate("transaction_date", ">=", $thirtyDaysAgo)
			->whereDate("transaction_date", "<=", $today)
			->count();

		// Query 4: Current month
		$currentMonthCount = DB::table("transactions")
			->where("user_id", $user->id)
			->whereDate("transaction_date", ">=", $startOfMonth)
			->whereDate("transaction_date", "<=", $endOfMonth)
			->count();

		return (object) [
			"today_total" => $todayTotal,
			"last_7_days" => $last7Days,
			"last_30_days" => $last30Days,
			"current_month_count" => $currentMonthCount,
		];
	}

	/**
	 * ALTERNATIF: Versi single query dengan raw SQL yang benar
	 */
	protected function getTransactionStatsSingleQuery(
		User $user,
		Carbon $now
	): object {
		$today = $now->toDateString();
		$sevenDaysAgo = $now
			->copy()
			->subDays(7)
			->toDateString();
		$thirtyDaysAgo = $now
			->copy()
			->subDays(30)
			->toDateString();
		$startOfMonth = $now
			->copy()
			->startOfMonth()
			->toDateString();
		$endOfMonth = $now
			->copy()
			->endOfMonth()
			->toDateString();

		$sql = "
            SELECT
                COUNT(CASE WHEN DATE(transaction_date) = ? THEN 1 END) as today_total,
                COUNT(CASE WHEN DATE(transaction_date) BETWEEN ? AND ? THEN 1 END) as last_7_days,
                COUNT(CASE WHEN DATE(transaction_date) BETWEEN ? AND ? THEN 1 END) as last_30_days,
                COUNT(CASE WHEN DATE(transaction_date) BETWEEN ? AND ? THEN 1 END) as current_month_count
            FROM transactions
            WHERE user_id = ?
        ";

		$result = DB::selectOne($sql, [
			$today,
			$sevenDaysAgo,
			$today,
			$thirtyDaysAgo,
			$today,
			$startOfMonth,
			$endOfMonth,
			$user->id,
		]);

		return $result ??
			(object) [
				"today_total" => 0,
				"last_7_days" => 0,
				"last_30_days" => 0,
				"current_month_count" => 0,
			];
	}

	/**
	 * Get monthly chart data
	 */
	public function getMonthlyChartData(User $user, Carbon $now): array
	{
		return DB::table("transactions")
			->where("user_id", $user->id)
			->whereYear("transaction_date", $now->year)
			->whereMonth("transaction_date", $now->month)
			->select(
				DB::raw("DAY(transaction_date) as day"),
				DB::raw(
					'SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as income'
				),
				DB::raw(
					'SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as expense'
				)
			)
			->groupBy(DB::raw("DAY(transaction_date)"))
			->orderBy("day")
			->get()
			->map(function ($item) {
				return [
					"day" => $item->day,
					"income" => $this->toMoney($item->income, isInteger: false)
						->getAmount()
						->toInt(),
					"expense" => $this->toMoney($item->expense, isInteger: false)
						->getAmount()
						->toInt(),
				];
			})
			->toArray();
	}

	/**
	 * Get recent activity
	 */
	public function getRecentActivity(User $user): array
	{
		return DB::table("activity_log")
			->where("causer_id", $user->id)
			->where("causer_type", User::class)
			->select("description", "properties", "created_at")
			->orderBy("created_at", "desc")
			->limit(5)
			->get()
			->toArray();
	}

	/**
	 * Get all transactions for current user with filters (cached)
	 */
	public function getUserTransactions(
		array $filters = [],
		bool $withRelations = true
	): Collection {
		$user = auth()->user();
		$cacheKey = Helper::generateCacheKey("user_transactions", [
			"user_id" => $user->id,
			"filters" => $filters,
			"with_relations" => $withRelations,
		]);

		return Cache::remember(
			$cacheKey,
			config("wallet.cache_ttl"),
			function () use ($user, $filters, $withRelations) {
				$query = Transaction::where("user_id", $user->id);

				if ($withRelations) {
					$query->with(["account", "toAccount", "category"]);
				}

				// Apply filters
				if (isset($filters["type"])) {
					$query->where("type", $filters["type"]);
				}

				if (isset($filters["account_id"])) {
					$query->where("account_id", $filters["account_id"]);
				}

				if (isset($filters["category_id"])) {
					$query->where("category_id", $filters["category_id"]);
				}

				if (isset($filters["description"])) {
					$query->where("description", "LIKE", "%{$filters["description"]}%");
				}

				if (isset($filters["start_date"])) {
					$query->whereDate("transaction_date", ">=", $filters["start_date"]);
				}

				if (isset($filters["end_date"])) {
					$query->whereDate("transaction_date", "<=", $filters["end_date"]);
				}

				if (isset($filters["payment_method"])) {
					$query->where("payment_method", $filters["payment_method"]);
				}

				if (isset($filters["is_recurring"])) {
					$query->where("is_recurring", $filters["is_recurring"]);
				}

				// Search in notes as well
				if (isset($filters["search"])) {
					$search = $filters["search"];
					$query->where(function ($q) use ($search) {
						$q->where("description", "LIKE", "%{$search}%")
							->orWhere("notes", "LIKE", "%{$search}%")
							->orWhere("reference_number", "LIKE", "%{$search}%");
					});
				}

				return $query
					->orderBy("transaction_date", "desc")
					->orderBy("created_at", "desc")
					->get();
			}
		);
	}

	/**
	 * Get paginated transactions
	 */
	public function getPaginatedTransactions(
		array $filters = [],
		int $perPage = 20
	): LengthAwarePaginator {
		$user = auth()->user();

		$query = Transaction::with(["account", "toAccount", "category"])->where(
			"user_id",
			$user->id
		);

		// Apply filters
		if (isset($filters["type"])) {
			$query->where("type", $filters["type"]);
		}

		if (isset($filters["account_id"])) {
			$query->where("account_id", $filters["account_id"]);
		}

		if (isset($filters["category_id"])) {
			$query->where("category_id", $filters["category_id"]);
		}

		if (isset($filters["description"])) {
			$query->where("description", "LIKE", "%{$filters["description"]}%");
		}

		if (isset($filters["start_date"]) && isset($filters["end_date"])) {
			$query->whereBetween("transaction_date", [
				$filters["start_date"],
				$filters["end_date"],
			]);
		} elseif (isset($filters["start_date"])) {
			$query->whereDate("transaction_date", ">=", $filters["start_date"]);
		} elseif (isset($filters["end_date"])) {
			$query->whereDate("transaction_date", "<=", $filters["end_date"]);
		}

		if (isset($filters["payment_method"])) {
			$query->where("payment_method", $filters["payment_method"]);
		}

		if (isset($filters["is_recurring"])) {
			$query->where("is_recurring", $filters["is_recurring"]);
		}

		// Search in notes as well
		if (isset($filters["search"])) {
			$search = $filters["search"];
			$query->where(function ($q) use ($search) {
				$q->where("description", "LIKE", "%{$search}%")
					->orWhere("notes", "LIKE", "%{$search}%")
					->orWhere("reference_number", "LIKE", "%{$search}%")
					->orWhereHas("category", function ($q) use ($search) {
						$q->where("name", "LIKE", "%{$search}%");
					})
					->orWhereHas("account", function ($q) use ($search) {
						$q->where("name", "LIKE", "%{$search}%");
					});
			});
		}

		return $query->orderBy("transaction_date", "desc")->paginate($perPage);
	}

	/**
	 * Create new transaction
	 */
	public function createTransaction(array $data): Transaction
	{
		DB::beginTransaction();

		try {
			// Generate UUID
			$data["uuid"] = \Illuminate\Support\Str::uuid();

			// Create transaction
			$transaction = Transaction::create($data);

			DB::commit();

			// Invalidate cache
			$this->invalidateTransactionCache($transaction->user_id);

			return $transaction->load(["account", "toAccount", "category"]);
		} catch (\Exception $e) {
			DB::rollBack();
			throw $e;
		}
	}

	/**
	 * Update transaction
	 */
	public function updateTransaction(
		Transaction $transaction,
		array $data
	): Transaction {
		DB::beginTransaction();

		try {
			// Save old values for balance reversal
			$oldAmount = $transaction->amount;
			$oldType = $transaction->type;
			$oldAccountId = $transaction->account_id;
			$oldToAccountId = $transaction->to_account_id;
			$oldCategoryId = $transaction->category_id;

			// Revert old balances
			$this->revertAccountBalance($transaction);

			// Update transaction
			$transaction->update($data);

			// Apply new balances
			$transaction = $transaction->fresh();

			DB::commit();

			// Invalidate cache
			$this->invalidateTransactionCache($transaction->user_id);

			return $transaction->load(["account", "toAccount", "category"]);
		} catch (\Exception $e) {
			DB::rollBack();
			throw $e;
		}
	}

	/**
	 * Delete transaction
	 */
	public function deleteTransaction(Transaction $transaction): bool
	{
		DB::beginTransaction();

		try {
			// Revert balances
			$this->revertAccountBalance($transaction);

			// Delete transaction
			$deleted = $transaction->delete();

			DB::commit();

			// Invalidate cache
			$this->invalidateTransactionCache($transaction->user_id);

			return $deleted;
		} catch (\Exception $e) {
			DB::rollBack();
			throw $e;
		}
	}

	/**
	 * Get transaction by UUID
	 */
	public function getByUuid(string $uuid): ?Transaction
	{
		$cacheKey = Helper::generateCacheKey("transaction_uuid", [
			"uuid" => $uuid,
		]);

		return Cache::remember(
			$cacheKey,
			config("wallet.cache_ttl"),
			function () use ($uuid) {
				return Transaction::with(["account", "toAccount", "category", "user"])
					->where("uuid", $uuid)
					->first();
			}
		);
	}

	/**
	 * Get recent transactions
	 */
	public function getRecentTransactions(
		int $userId,
		int $limit = 10
	): Collection {
		$cacheKey = Helper::generateCacheKey("recent_transactions", [
			"user_id" => $userId,
			"limit" => $limit,
		]);

		return Cache::remember(
			$cacheKey,
			config("wallet.cache_ttl") / 2, // Shorter cache for recent data
			function () use ($userId, $limit) {
				return Transaction::with(["account", "category"])
					->where("user_id", $userId)
					->orderBy("transaction_date", "desc")
					->orderBy("created_at", "desc")
					->limit($limit)
					->get();
			}
		);
	}

	/**
	 * Get transactions summary by type for period
	 */
	public function getSummaryByType(
		int $userId,
		string $startDate,
		string $endDate
	): array {
		$cacheKey = Helper::generateCacheKey("transactions_summary", [
			"user_id" => $userId,
			"start_date" => $startDate,
			"end_date" => $endDate,
		]);

		return Cache::remember(
			$cacheKey,
			config("wallet.cache_ttl"),
			function () use ($userId, $startDate, $endDate) {
				$results = \DB::table("transactions")
					->where("user_id", $userId)
					->whereBetween("transaction_date", [$startDate, $endDate])
					->select(
						"type",
						DB::raw("SUM(amount) as total_amount"),
						DB::raw("COUNT(*) as count")
					)
					->groupBy("type")
					->get()
					->pluck("total_amount", "type")
					->toArray();

				// Ensure all types exist in array
				$types = [
					TransactionType::INCOME,
					TransactionType::EXPENSE,
					TransactionType::TRANSFER,
				];
				foreach ($types as $type) {
					if (!isset($results[$type->value])) {
						$results[$type->value] = 0;
					}
				}

				return $results;
			}
		);
	}

	/**
	 * Get category spending for period
	 */
	public function getCategorySpending(
		int $userId,
		string $startDate,
		string $endDate
	): Collection {
		$cacheKey = Helper::generateCacheKey("category_spending", [
			"user_id" => $userId,
			"start_date" => $startDate,
			"end_date" => $endDate,
		]);

		return Cache::remember(
			$cacheKey,
			config("wallet.cache_ttl"),
			function () use ($userId, $startDate, $endDate) {
				return DB::table("transactions")
					->join("categories", "transactions.category_id", "=", "categories.id")
					->select(
						"categories.id",
						"categories.name",
						"categories.type",
						"categories.icon",
						DB::raw("SUM(transactions.amount) as total_spent"),
						DB::raw("COUNT(transactions.id) as transaction_count")
					)
					->where("transactions.user_id", $userId)
					->where("transactions.type", TransactionType::EXPENSE)
					->whereBetween("transactions.transaction_date", [
						$startDate,
						$endDate,
					])
					->groupBy(
						"categories.id",
						"categories.name",
						"categories.type",
						"categories.icon"
					)
					->orderBy("total_spent", "desc")
					->get();
			}
		);
	}

	/**
	 * Get daily transaction totals for period
	 */
	public function getDailyTotals(
		int $userId,
		string $startDate,
		string $endDate
	): Collection {
		return Transaction::where("user_id", $userId)
			->whereBetween("transaction_date", [$startDate, $endDate])
			->select(
				DB::raw("DATE(transaction_date) as date"),
				DB::raw(
					'SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as income'
				),
				DB::raw(
					'SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as expense'
				),
				DB::raw("COUNT(*) as count")
			)
			->groupBy("date")
			->orderBy("date")
			->get();
	}

	/**
	 * Get transactions by account
	 */
	public function getByAccount(int $accountId, array $filters = []): Collection
	{
		$query = Transaction::with(["account", "category"])
			->where("account_id", $accountId)
			->orWhere("to_account_id", $accountId);

		if (isset($filters["start_date"])) {
			$query->whereDate("transaction_date", ">=", $filters["start_date"]);
		}

		if (isset($filters["end_date"])) {
			$query->whereDate("transaction_date", "<=", $filters["end_date"]);
		}

		if (isset($filters["type"])) {
			$query->where("type", $filters["type"]);
		}

		return $query->orderBy("transaction_date", "desc")->get();
	}

	/**
	 * Get transactions by category
	 */
	public function getByCategory(
		int $categoryId,
		array $filters = []
	): Collection {
		$query = Transaction::with(["account", "category"])->where(
			"category_id",
			$categoryId
		);

		if (isset($filters["start_date"])) {
			$query->whereDate("transaction_date", ">=", $filters["start_date"]);
		}

		if (isset($filters["end_date"])) {
			$query->whereDate("transaction_date", "<=", $filters["end_date"]);
		}

		if (isset($filters["type"])) {
			$query->where("type", $filters["type"]);
		}

		return $query->orderBy("transaction_date", "desc")->get();
	}

	/**
	 * Get monthly transaction trends
	 */
	public function getMonthlyTrends(int $userId, int $months = 6): array
	{
		$endDate = Carbon::now()->endOfMonth();
		$startDate = Carbon::now()
			->subMonths($months - 1)
			->startOfMonth();

		$results = Transaction::where("user_id", $userId)
			->whereBetween("transaction_date", [$startDate, $endDate])
			->select(
				DB::raw("YEAR(transaction_date) as year"),
				DB::raw("MONTH(transaction_date) as month"),
				DB::raw(
					'SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as income'
				),
				DB::raw(
					'SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as expense'
				)
			)
			->groupBy("year", "month")
			->orderBy("year", "asc")
			->orderBy("month", "asc")
			->get();

		$trends = [];
		foreach ($results as $result) {
			$monthName = Carbon::create($result->year, $result->month, 1)->format(
				"M Y"
			);
			$trends[$monthName] = [
				"income" => $result->income,
				"expense" => $result->expense,
				"net" => $result->income - $result->expense,
			];
		}

		return $trends;
	}

	/**
	 * Revert account balance from transaction
	 */
	private function revertAccountBalance(Transaction $transaction): void
	{
		$accountRepo = app(AccountRepository::class);

		switch ($transaction->type) {
			case TransactionType::INCOME:
				$accountRepo->updateBalance(
					$transaction->account_id,
					$transaction->amount->getAmount()->toInt(),
					false
				);
				break;

			case TransactionType::EXPENSE:
				$accountRepo->updateBalance(
					$transaction->account_id,
					$transaction->amount->getAmount()->toInt(),
					true
				);
				break;

			case TransactionType::TRANSFER:
				$accountRepo->updateBalance(
					$transaction->account_id,
					$transaction->amount->getAmount()->toInt(),
					true
				);
				if ($transaction->to_account_id) {
					$accountRepo->updateBalance(
						$transaction->to_account_id,
						$transaction->amount->getAmount()->toInt(),
						false
					);
				}
				break;
		}
	}

	/**
	 * Update budget spent amount
	 */
	private function updateBudgetSpent(
		Transaction $transaction,
		string $operation
	): void {
		$budgetRepo = app(BudgetRepository::class);

		// Find active budget for this category and date
		$budget = $budgetRepo->getActiveBudgetForDate(
			$transaction->category,
			$transaction->user_id,
			$transaction->transaction_date
		);

		if ($budget) {
			if ($operation === "add") {
				$budgetRepo->updateSpentAmount(
					$budget->id,
					$transaction->amount,
					"add"
				);
			} else {
				$budgetRepo->updateSpentAmount(
					$budget->id,
					$transaction->amount,
					"subtract"
				);
			}
		}
	}

	/**
	 * Get active budget for date (helper method)
	 */
	private function getActiveBudgetForDate(
		Category $category,
		int $userId,
		Carbon $date
	): ?\Modules\Wallet\Models\Budget {
		$budgetRepo = app(BudgetRepository::class);
		return $budgetRepo->getActiveBudgetForDate($category, $userId, $date);
	}

	/**
	 * Invalidate transaction cache
	 */
	private function invalidateTransactionCache(int $userId): void
	{
		$patterns = [
			Helper::generateCacheKey("user_transactions", [
				"user_id" => $userId,
				"*",
			]),
			Helper::generateCacheKey("recent_transactions", [
				"user_id" => $userId,
				"*",
			]),
			Helper::generateCacheKey("transactions_summary", [
				"user_id" => $userId,
				"*",
			]),
			Helper::generateCacheKey("category_spending", [
				"user_id" => $userId,
				"*",
			]),
			Helper::generateCacheKey("transaction_stats", ["user_id" => $userId]),
		];

		foreach ($patterns as $pattern) {
			$this->clearCacheByPattern($pattern);
		}
	}

	/**
	 * Clear cache by pattern
	 */
	private function clearCacheByPattern(string $pattern): void
	{
		if (method_exists(Cache::store(), "tags")) {
			Cache::tags(["transactions"])->flush();
		} else {
			// Simple implementation - flush all wallet cache
			Cache::flush();
		}
	}

	/**
	 * Get dashboard summary for transactions
	 */
	public function getDashboardSummary(User $user): array
	{
		$now = Carbon::now();
		$startOfMonth = $now->copy()->startOfMonth();
		$endOfMonth = $now->copy()->endOfMonth();

		$summary = $this->getSummaryByType(
			$user->id,
			$startOfMonth->format("Y-m-d"),
			$endOfMonth->format("Y-m-d")
		);

		$categorySpending = $this->getCategorySpending(
			$user->id,
			$startOfMonth->format("Y-m-d"),
			$endOfMonth->format("Y-m-d")
		);

		$recentTransactions = $this->getRecentTransactions($user->id, 5);

		$stats = $this->getTransactionStats($user->id);

		return [
			"summary" => $summary,
			"category_spending" => $categorySpending,
			"recent_transactions" => $recentTransactions,
			"stats" => $stats,
			"period" => [
				"start" => $startOfMonth,
				"end" => $endOfMonth,
			],
		];
	}

	/**
	 * Get transactions for export
	 */
	public function getForExport(
		int $userId,
		?string $startDate = null,
		?string $endDate = null
	): Collection {
		$query = Transaction::with(["account", "toAccount", "category"])->where(
			"user_id",
			$userId
		);

		if ($startDate) {
			$query->whereDate("transaction_date", ">=", $startDate);
		}

		if ($endDate) {
			$query->whereDate("transaction_date", "<=", $endDate);
		}

		return $query
			->orderBy("transaction_date", "desc")
			->get()
			->map(function ($transaction) {
				return [
					"Tanggal" => $transaction->transaction_date->format("d/m/Y"),
					"Waktu" => $transaction->transaction_date->format("H:i"),
					"Tipe" => $transaction->type->name,
					"Deskripsi" => $transaction->description,
					"Kategori" => $transaction->category->name,
					"Akun" => $transaction->account->name,
					"Akun Tujuan" => $transaction->toAccount
						? $transaction->toAccount->name
						: "",
					"Jumlah" => $transaction->amount->getAmount()->toInt(),
					"Catatan" => $transaction->notes,
					"Metode Pembayaran" => $transaction->payment_method,
					"Nomor Referensi" => $transaction->reference_number,
				];
			});
	}

	/**
	 * Import transactions from array
	 */
	public function importTransactions(array $transactions, User $user): array
	{
		DB::beginTransaction();

		$results = [
			"success" => 0,
			"failed" => 0,
			"errors" => [],
		];

		try {
			foreach ($transactions as $index => $data) {
				try {
					// Validate required fields
					$required = [
						"account_id",
						"category_id",
						"type",
						"amount",
						"description",
						"transaction_date",
					];
					foreach ($required as $field) {
						if (!isset($data[$field]) || empty($data[$field])) {
							throw new \Exception("Field {$field} is required");
						}
					}

					// Set user_id
					$data["user_id"] = $user->id;

					// Parse date
					$data["transaction_date"] = Carbon::parse($data["transaction_date"]);

					// Create transaction
					$this->createTransaction($data);

					$results["success"]++;
				} catch (\Exception $e) {
					$results["failed"]++;
					$results["errors"][] = "Row {$index}: " . $e->getMessage();
				}
			}

			DB::commit();
			return $results;
		} catch (\Exception $e) {
			DB::rollBack();
			throw $e;
		}
	}

	/**
	 * Duplicate transaction
	 */
	public function duplicateTransaction(Transaction $transaction): Transaction
	{
		$newData = $transaction->toArray();

		// Remove ID and timestamps
		unset(
			$newData["id"],
			$newData["uuid"],
			$newData["created_at"],
			$newData["updated_at"],
			$newData["deleted_at"]
		);

		// Set new UUID
		$newData["uuid"] = \Illuminate\Support\Str::uuid();

		// Set transaction date to today
		$newData["transaction_date"] = Carbon::now();

		return $this->createTransaction($newData);
	}

	/**
	 * Bulk update transactions
	 */
	public function bulkUpdate(array $ids, array $data): int
	{
		$user = auth()->user();

		return Transaction::where("user_id", $user->id)
			->whereIn("id", $ids)
			->update($data);
	}

	/**
	 * Bulk delete transactions
	 */
	public function bulkDelete(array $ids): int
	{
		$user = auth()->user();
		$deleted = 0;

		DB::beginTransaction();

		try {
			$transactions = Transaction::where("user_id", $user->id)
				->whereIn("id", $ids)
				->get();

			foreach ($transactions as $transaction) {
				$this->deleteTransaction($transaction);
				$deleted++;
			}

			DB::commit();
			return $deleted;
		} catch (\Exception $e) {
			DB::rollBack();
			throw $e;
		}
	}

	/**
	 * Get transactions by date range with daily summary
	 */
	public function getDailySummary(
		int $userId,
		string $startDate,
		string $endDate
	): Collection {
		return Transaction::where("user_id", $userId)
			->whereBetween("transaction_date", [$startDate, $endDate])
			->select(
				DB::raw("DATE(transaction_date) as date"),
				DB::raw("COUNT(*) as total_transactions"),
				DB::raw(
					'SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as total_income'
				),
				DB::raw(
					'SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as total_expense'
				),
				DB::raw(
					'SUM(CASE WHEN type = "transfer" THEN amount ELSE 0 END) as total_transfer'
				)
			)
			->groupBy("date")
			->orderBy("date", "desc")
			->get();
	}

	/**
	 * Search transactions with advanced filters
	 */
	public function searchAdvanced(array $filters): Collection
	{
		$user = auth()->user();
		$query = Transaction::with(["account", "toAccount", "category"])->where(
			"user_id",
			$user->id
		);

		// Amount range
		if (isset($filters["min_amount"])) {
			$query->where("amount", ">=", $filters["min_amount"]);
		}

		if (isset($filters["max_amount"])) {
			$query->where("amount", "<=", $filters["max_amount"]);
		}

		// Date range
		if (isset($filters["start_date"])) {
			$query->whereDate("transaction_date", ">=", $filters["start_date"]);
		}

		if (isset($filters["end_date"])) {
			$query->whereDate("transaction_date", "<=", $filters["end_date"]);
		}

		// Multiple types
		if (isset($filters["types"]) && is_array($filters["types"])) {
			$query->whereIn("type", $filters["types"]);
		}

		// Multiple categories
		if (isset($filters["category_ids"]) && is_array($filters["category_ids"])) {
			$query->whereIn("category_id", $filters["category_ids"]);
		}

		// Multiple accounts
		if (isset($filters["account_ids"]) && is_array($filters["account_ids"])) {
			$query->where(function ($q) use ($filters) {
				$q->whereIn("account_id", $filters["account_ids"])->orWhereIn(
					"to_account_id",
					$filters["account_ids"]
				);
			});
		}

		// Has notes
		if (isset($filters["has_notes"])) {
			if ($filters["has_notes"]) {
				$query->whereNotNull("notes")->where("notes", "!=", "");
			} else {
				$query->where(function ($q) {
					$q->whereNull("notes")->orWhere("notes", "");
				});
			}
		}

		// Is recurring
		if (isset($filters["is_recurring"])) {
			$query->where("is_recurring", $filters["is_recurring"]);
		}

		// Sort by
		$sortBy = $filters["sort_by"] ?? "transaction_date";
		$sortOrder = $filters["sort_order"] ?? "desc";
		$query->orderBy($sortBy, $sortOrder);

		return $query->get();
	}
}
