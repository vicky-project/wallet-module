<?php

namespace Modules\Wallet\Repositories;

use Modules\Wallet\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class TransactionRepository
{
	protected $transaction;

	public function __construct(Transaction $transaction)
	{
		$this->transaction = $transaction;
	}

	public function getUserTransactions(array $filters = [])
	{
		$query = $this->transaction
			->where("user_id", Auth::id())
			->with(["wallet", "toWallet"])
			->orderBy("transaction_date", "desc")
			->orderBy("created_at", "desc");

		// Apply filters
		if (isset($filters["type"])) {
			$query->where("type", $filters["type"]);
		}

		if (isset($filters["wallet_id"])) {
			$query->where(function ($q) use ($filters) {
				$q->where("wallet_id", $filters["wallet_id"])->orWhere(
					"to_wallet_id",
					$filters["wallet_id"]
				);
			});
		}

		if (isset($filters["start_date"]) && isset($filters["end_date"])) {
			$query->whereBetween("transaction_date", [
				$filters["start_date"],
				$filters["end_date"],
			]);
		}

		if (isset($filters["category"])) {
			$query->where("category", $filters["category"]);
		}

		if (isset($filters["search"])) {
			$search = $filters["search"];
			$query->where(function ($q) use ($search) {
				$q->where("description", "like", "%{$search}%")
					->orWhere("reference_number", "like", "%{$search}%")
					->orWhere("transaction_code", "like", "%{$search}%");
			});
		}

		return $query
			->get()
			->groupBy(fn($transaction) => $transaction->created_at->format("Y F"));
	}

	public function createTransaction(array $data)
	{
		$data["user_id"] = Auth::id();

		if (empty($data["transaction_code"])) {
			$data["transaction_code"] = "TRX" . strtoupper(uniqid());
		}

		return $this->transaction->create($data);
	}

	public function updateTransaction(Transaction $transaction, array $data)
	{
		// Don't allow updating certain fields if transaction is completed
		if ($transaction->isCompleted()) {
			unset($data["amount"], $data["type"], $data["wallet_id"]);
		}

		$transaction->update($data);
		return $transaction;
	}

	public function getTransactionSummary(
		$userId = null,
		$startDate = null,
		$endDate = null
	) {
		$userId = $userId ?? Auth::id();

		$query = $this->transaction
			->where("user_id", $userId)
			->where("status", "completed");

		if ($startDate && $endDate) {
			$query->whereBetween("transaction_date", [$startDate, $endDate]);
		}

		return $query
			->selectRaw(
				'
            type,
            COUNT(*) as count,
            SUM(amount) as total_amount
        '
			)
			->groupBy("type")
			->get();
	}

	public function getRecentTransactions($limit = 10)
	{
		return $this->transaction
			->where("user_id", Auth::id())
			->with(["wallet", "toWallet"])
			->latest()
			->limit($limit)
			->get();
	}

	public function getTransactionByCode($code)
	{
		return $this->transaction
			->where("transaction_code", $code)
			->with(["wallet", "toWallet", "toAccount", "user"])
			->firstOrFail();
	}

	public function getUnreconciledTransactions($walletId = null)
	{
		$query = $this->transaction
			->where("user_id", Auth::id())
			->where("is_reconciled", false)
			->where("status", "completed");

		if ($walletId) {
			$query->where("wallet_id", $walletId);
		}

		return $query->orderBy("transaction_date")->get();
	}
}
