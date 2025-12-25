<?php

namespace Modules\Wallet\Services;

use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Models\Transaction;
use Modules\Wallet\Interfaces\TransactionServiceInterface;
use Brick\Money\Money;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;

class TransactionService implements TransactionServiceInterface
{
	public function deposit(Wallet $wallet, array $data): Transaction
	{
		return DB::transaction(function () use ($wallet, $data) {
			$amount = Money::of($data["amount"], $wallet->currency);

			// Create transaction record
			$transaction = Transaction::create([
				"wallet_id" => $wallet->id,
				"user_id" => auth()->id(),
				"type" => "deposit",
				"amount" => $amount,
				"currency" => $wallet->currency,
				"transaction_date" => $data["transaction_date"] ?? now(),
				"category" => $data["category"] ?? "deposit",
				"description" => $data["description"] ?? "Deposit",
				"status" => "completed",
				"reference_number" => $data["reference_number"] ?? null,
			]);

			// Update wallet balance
			$wallet->deposit($amount);

			return $transaction;
		});
	}

	public function withdraw(Wallet $wallet, array $data): Transaction
	{
		return DB::transaction(function () use ($wallet, $data) {
			$amount = Money::of($data["amount"], $wallet->currency);

			// Create transaction record
			$transaction = Transaction::create([
				"wallet_id" => $wallet->id,
				"user_id" => auth()->id(),
				"type" => "withdraw",
				"amount" => $amount,
				"currency" => $wallet->currency,
				"transaction_date" => $data["transaction_date"] ?? now(),
				"category" => $data["category"] ?? "withdrawal",
				"description" => $data["description"] ?? "Withdrawal",
				"status" => "completed",
				"reference_number" => $data["reference_number"] ?? null,
			]);

			// Update wallet balance
			$wallet->withdraw($amount);

			return $transaction;
		});
	}

	public function transfer(
		Wallet $fromWallet,
		Wallet $toWallet,
		array $data
	): array {
		return DB::transaction(function () use ($fromWallet, $toWallet, $data) {
			$amount = Money::of($data["amount"], $fromWallet->currency);

			// Execute transfer
			$transferResult = $fromWallet->transferTo($toWallet, $amount);

			// Create outgoing transaction
			$outgoingTransaction = Transaction::create([
				"wallet_id" => $fromWallet->id,
				"to_wallet_id" => $toWallet->id,
				"to_account_id" => $toWallet->account_id,
				"user_id" => auth()->id(),
				"type" => "transfer",
				"amount" => $amount,
				"currency" => $fromWallet->currency,
				"transaction_date" => $data["transaction_date"] ?? now(),
				"category" => "Transfer Out",
				"description" =>
					$data["description"] ?? "Transfer to {$toWallet->name}",
				"status" => "completed",
				"reference_number" => $data["reference_number"] ?? "TRF" . time(),
			]);

			// Create incoming transaction
			$incomingTransaction = Transaction::create([
				"wallet_id" => $toWallet->id,
				"to_wallet_id" => $fromWallet->id,
				"to_account_id" => $fromWallet->account_id,
				"user_id" => $toWallet->account->user_id,
				"type" => "deposit",
				"amount" => $amount,
				"currency" => $toWallet->currency,
				"transaction_date" => $data["transaction_date"] ?? now(),
				"category" => "Transfer In",
				"description" =>
					$data["description"] ?? "Transfer from {$fromWallet->name}",
				"status" => "completed",
				"reference_number" => $data["reference_number"] ?? "TRF" . time(),
			]);

			return [
				"outgoing" => $outgoingTransaction,
				"incoming" => $incomingTransaction,
				"transfer" => $transferResult,
			];
		});
	}

	public function recordTransaction(array $data): Transaction
	{
		$wallet = Wallet::findOrFail($data["wallet_id"]);

		return match ($data["type"]) {
			"deposit" => $this->deposit($wallet, $data),
			"withdraw" => $this->withdraw($wallet, $data),
			default => throw new \Exception(
				"Invalid transaction type: {$data["type"]}"
			),
		};
	}

	public function updateTransactionStatus(
		Transaction $transaction,
		string $status,
		array $data = []
	): Transaction {
		// Status update logic with balance adjustment
		return DB::transaction(function () use ($transaction, $status, $data) {
			$oldStatus = $transaction->status;
			$transaction->update(array_merge($data, ["status" => $status]));

			// Handle balance adjustments for status changes
			$this->handleStatusChangeBalance($transaction, $oldStatus, $status);

			return $transaction;
		});
	}

	private function handleStatusChangeBalance(
		Transaction $transaction,
		string $oldStatus,
		string $newStatus
	): void {
		$wallet = $transaction->wallet;

		// If moving from pending to completed, apply transaction
		if ($oldStatus !== "completed" && $newStatus === "completed") {
			if ($transaction->isDeposit()) {
				$wallet->deposit($transaction->amount, $transaction->fee);
			} elseif ($transaction->isWithdraw()) {
				$wallet->withdraw($transaction->amount, $transaction->fee);
			}
		}
		// If moving from completed to non-completed, reverse transaction
		elseif ($oldStatus === "completed" && $newStatus !== "completed") {
			if ($transaction->isDeposit()) {
				$wallet->withdraw(
					$transaction->net_amount,
					Money::zero($wallet->currency)
				);
			} elseif ($transaction->isWithdraw()) {
				$wallet->deposit(
					$transaction->net_amount,
					Money::zero($wallet->currency)
				);
			}
		}
	}

	public function getWalletBalance(Wallet $wallet, ?string $date = null): Money
	{
		return $date
			? $this->calculateHistoricalBalance($wallet, $date)
			: $wallet->balance;
	}

	public function getAccountBalance(int $accountId, ?string $date = null): Money
	{
		$account = \Modules\Wallet\Models\Account::findOrFail($accountId);
		$total = Money::zero($account->currency);

		foreach ($account->wallets as $wallet) {
			$total = $total->plus($this->getWalletBalance($wallet, $date));
		}

		return $total;
	}

	private function calculateHistoricalBalance(
		Wallet $wallet,
		string $date
	): Money {
		$balance = $wallet->initial_balance;

		// Add deposits and incoming transfers up to date
		$deposits = $wallet
			->transactions()
			->whereDate("transaction_date", "<=", $date)
			->where("status", "completed")
			->where(function ($q) {
				$q->where("type", "deposit")->orWhere(function ($q2) {
					$q2
						->where("type", "transfer")
						->where("to_wallet_id", \DB::raw("wallet_id"));
				});
			})
			->get()
			->sum(fn($t) => $t->net_amount->getMinorAmount()->toInt());

		// Subtract withdrawals and outgoing transfers
		$withdrawals = $wallet
			->transactions()
			->whereDate("transaction_date", "<=", $date)
			->where("status", "completed")
			->where(function ($q) {
				$q->where("type", "withdraw")->orWhere(function ($q2) {
					$q2
						->where("type", "transfer")
						->where("wallet_id", \DB::raw("wallet_id"));
				});
			})
			->get()
			->sum(fn($t) => $t->net_amount->getMinorAmount()->toInt());

		return $wallet->initial_balance
			->plus(Money::ofMinor($deposits, $wallet->currency))
			->minus(Money::ofMinor($withdrawals, $wallet->currency));
	}
}
