<?php

namespace Modules\Wallet\Repositories;

use App\Models\User;
use Brick\Money\Money;
use Brick\Math\RoundingMode;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Wallet\Models\Account;
use Modules\Wallet\Models\Category;
use Modules\Wallet\Models\Transaction;
use Modules\Wallet\Enums\TransactionType;

class TransactionRepository extends BaseRepository
{
	public function __construct(Transaction $model)
	{
		parent::__construct($model);
	}

	/**
	 * Get transactions with filters
	 */
	public function getWithFilters(User $user, array $filters = [])
	{
		$query = $this->model
			->with(["category", "account"])
			->where("user_id", $user->id);

		// Apply filters
		if (!empty($filters["type"])) {
			$query->where("type", $filters["type"]);
		}

		if (!empty($filters["category_id"])) {
			$query->where("category_id", $filters["category_id"]);
		}

		if (!empty($filters["account_id"])) {
			$query->where("account_id", $filters["account_id"]);
		}

		if (!empty($filters["month"]) && !empty($filters["year"])) {
			$query
				->whereMonth("transaction_date", $filters["month"])
				->whereYear("transaction_date", $filters["year"]);
		}

		if (!empty($filters["search"])) {
			$search = $filters["search"];
			$query->where(function ($q) use ($search) {
				$q->where("title", "like", "%{$search}%")->orWhere(
					"description",
					"like",
					"%{$search}%"
				);
			});
		}

		return $query
			->orderBy("transaction_date", "desc")
			->orderBy("created_at", "desc")
			->paginate(20)
			->through(function ($transaction) {
				$transaction->formatted_amount = $this->formatMoney(
					$this->fromDatabaseAmount($transaction->amount->getAmount()->toInt())
				);
				return $transaction;
			});
	}

	/**
	 * Get transactions by type
	 */
	public function getByType(User $user, string $type)
	{
		return $this->model
			->with(["category", "account"])
			->where("user_id", $user->id)
			->where("type", $type)
			->orderBy("transaction_date", "desc")
			->orderBy("created_at", "desc")
			->paginate(20)
			->through(function ($transaction) {
				$transaction->formatted_amount = $this->formatMoney(
					$this->fromDatabaseAmount($transaction->amount)
				);
				return $transaction;
			});
	}

	/**
	 * Create transaction with Money amounts
	 */
	public function createTransaction(array $data, User $user): Transaction
	{
		// Convert amount to Money and then to database format
		$data["amount"] = $this->toDatabaseAmount($this->toMoney($data["amount"]));

		$data["user_id"] = $user->id;

		// If no transaction date, use today
		if (!isset($data["transaction_date"])) {
			$data["transaction_date"] = Carbon::now();
		}

		$transaction = $this->create($data);

		// Update account balance
		if ($transaction->account_id) {
			$this->updateAccountBalance($transaction);
		}

		return $transaction;
	}

	/**
	 * Update transaction
	 */
	public function updateTransaction(int $id, array $data): Transaction
	{
		// Store old amount for account balance adjustment
		$oldTransaction = $this->find($id);
		$oldAmount = $oldTransaction->amount;

		if (isset($data["amount"])) {
			$data["amount"] = $this->toDatabaseAmount(
				$this->toMoney($data["amount"])
			);
		}

		if (!isset($data["is_recurring"]) && $oldTransaction->is_recurring) {
			$data["is_recurring"] = false;
		}

		$this->update($id, $data);
		$transaction = $this->find($id);

		// Update account balance if amount changed
		if ($transaction->account_id && $oldAmount != $transaction->amount) {
			$this->adjustAccountBalance($oldTransaction, $transaction);
		}

		return $transaction;
	}

	/**
	 * Delete transaction and adjust account balance
	 */
	public function deleteTransaction(int $id): bool
	{
		$transaction = $this->find($id);

		if ($transaction->account_id) {
			$this->reverseAccountBalance($transaction);
		}

		return $this->delete($id);
	}

	/**
	 * Get recent transactions
	 */
	public function getRecentTransactions(User $user, int $limit = 10): Collection
	{
		return $this->model
			->with("category")
			->where("user_id", $user->id)
			->orderBy("transaction_date", "desc")
			->orderBy("created_at", "desc")
			->limit($limit)
			->get()
			->map(function ($transaction) {
				$transaction->formatted_amount = $this->formatMoney(
					$this->fromDatabaseAmount($transaction->amount->getAmount()->toInt())
				);
				return $transaction;
			});
	}

	/**
	 * Get transactions by period
	 */
	public function getByPeriod(
		User $user,
		string $period = "monthly",
		?Carbon $date = null
	): Collection {
		$date = $date ?? Carbon::now();

		$query = $this->model->with("category")->where("user_id", $user->id);

		switch ($period) {
			case "daily":
				$query->whereDate("transaction_date", $date->toDateString());
				break;
			case "weekly":
				$query->whereBetween("transaction_date", [
					$date->startOfWeek(),
					$date->endOfWeek(),
				]);
				break;
			case "monthly":
				$query
					->whereMonth("transaction_date", $date->month)
					->whereYear("transaction_date", $date->year);
				break;
			case "yearly":
				$query->whereYear("transaction_date", $date->year);
				break;
		}

		return $query->orderBy("transaction_date", "desc")->get();
	}

	/**
	 * Get summary statistics
	 */
	public function getSummary(
		User $user,
		int $month = null,
		int $year = null
	): array {
		$currency = "IDR";

		$transactions = $this->model
			->where("user_id", $user->id)
			->when(
				$month,
				fn($query, $month) => $query->whereMonth("transaction_date", $month)
			)
			->when(
				$year,
				fn($query, $year) => $query->whereYear("transaction_date", $year)
			)
			->get();

		$income = $transactions
			->where("type", TransactionType::INCOME)
			->sum(
				fn(Transaction $transaction) => $transaction->amount
					->getAmount()
					->toInt()
			);
		$expense = $transactions
			->where("type", TransactionType::EXPENSE)
			->sum(
				fn(Transaction $transaction) => $transaction->amount
					->getAmount()
					->toInt()
			);

		return [
			"income" => $this->fromDatabaseAmount($income, $currency, true),
			"expense" => $this->fromDatabaseAmount($expense, $currency, true),
			"net_balance" => $this->fromDatabaseAmount(
				$income - $expense,
				$currency,
				true
			),
			"total_transactions" => $transactions->count(),
		];
	}

	/**
	 * Update account balance after transaction
	 */
	private function updateAccountBalance(Transaction $transaction): void
	{
		$account = Account::find($transaction->account_id);
		if (!$account) {
			return;
		}

		$amount = $this->fromDatabaseAmount(
			$transaction->amount->getAmount()->toInt(),
			$account->currency,
			true
		);

		if ($transaction->type === TransactionType::INCOME) {
			$account->current_balance = $account->current_balance->plus(
				$amount->getAmount()->toInt()
			);
		} else {
			$account->current_balance = $account->current_balance->minus(
				$amount->getAmount()->toInt()
			);
		}

		$account->save();
	}

	/**
	 * Adjust account balance when transaction changes
	 */
	private function adjustAccountBalance(
		Transaction $oldTransaction,
		Transaction $newTransaction
	): void {
		// Reverse old transaction
		$this->reverseAccountBalance($oldTransaction);

		// Apply new transaction
		$this->updateAccountBalance($newTransaction);
	}

	/**
	 * Reverse account balance
	 */
	private function reverseAccountBalance(Transaction $transaction): void
	{
		$account = Account::find($transaction->account_id);
		if (!$account) {
			return;
		}

		$amount = $this->fromDatabaseAmount($transaction->amount);

		// Reverse the effect
		if ($transaction->type === "income") {
			$account->current_balance = $this->toDatabaseAmount(
				$this->fromDatabaseAmount($account->current_balance)->minus($amount)
			);
		} else {
			$account->current_balance = $this->toDatabaseAmount(
				$this->fromDatabaseAmount($account->current_balance)->plus($amount)
			);
		}

		$account->save();
	}
}
