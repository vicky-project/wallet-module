<?php

namespace Modules\Wallet\Services\Balances;

use Modules\Wallet\Models\Transaction;
use Modules\Wallet\Enums\TransactionType;
use Modules\Wallet\Repositories\AccountRepository;
use Modules\Wallet\Repositories\TransactionRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BalanceUpdateManager
{
	private BalanceUpdaterFactory $factory;
	private TransactionRepository $transactionRepo;

	public function __construct(
		BalanceUpdaterFactory $factory,
		TransactionRepository $transactionRepo
	) {
		$this->factory = $factory;
		$this->transactionRepo = $transactionRepo;
	}

	/**
	 * Handle delete transaction - revert balance changes
	 */
	public function deleteTransaction(
		int $transactionId,
		bool $force = false
	): void {
		DB::transaction(function () use ($transactionId, $force) {
			$transaction = $this->transactionRepo->findOrFail($transactionId);

			if (!$force) {
				$this->validateDeletion($transaction);
			} else {
				\Log::info("Force deletion of transaction.", [
					"transaction_id" => $transaction->id,
					"user_id" => auth()->id(),
					"bypass_validation" => true,
				]);
			}

			// Get the appropriate updater
			$updater = $this->factory->getUpdater($transaction);

			// Revert the balance changes
			$updater->revert($transaction);

			// Delete the transaction
			$transaction->delete();
		});
	}

	/**
	 * Handle restore soft-deleted transaction - apply balance changes
	 */
	public function restoreTransaction(int $transactionId): void
	{
		DB::transaction(function () use ($transactionId) {
			// Find the soft-deleted transaction
			$transaction = $this->transactionRepo->findTrashedOrFail($transactionId);

			// Get the appropriate updater
			$updater = $this->factory->getUpdater($transaction);

			// Apply the balance changes
			$updater->apply($transaction);

			// Restore the transaction
			$transaction->restore();
		});
	}

	/**
	 * Handle update transaction - revert old and apply new balance changes
	 */
	public function updateTransaction(
		int $transactionId,
		array $data
	): Transaction {
		return DB::transaction(function () use ($transactionId, $data) {
			// Find existing transaction
			$transaction = $this->transactionRepo->findOrFail($transactionId);

			// Clone the old transaction before updating
			$oldTransaction = clone $transaction;

			// Update transaction with new data
			$transaction->fill($data);

			// Handle balance update based on what changed
			$this->handleUpdateBalance($oldTransaction, $transaction);

			// Save the updated transaction
			$transaction->save();

			return $transaction->fresh();
		});
	}

	/**
	 * Smart balance update handling for transaction changes
	 */
	private function handleUpdateBalance(
		Transaction $oldTransaction,
		Transaction $newTransaction
	): void {
		// If nothing changed that affects balance, do nothing
		if (
			$this->balanceAffectingFieldsChanged($oldTransaction, $newTransaction)
		) {
			// Get updaters for both old and new transactions
			$oldUpdater = $this->factory->getUpdater($oldTransaction);
			$newUpdater = $this->factory->getUpdater($newTransaction);

			$oldUpdater->revert($oldTransaction);
			$newUpdater->apply($newTransaction);
		}
	}

	/**
	 * Check if any balance-affecting fields changed
	 */
	private function balanceAffectingFieldsChanged(
		Transaction $old,
		Transaction $new
	): bool {
		return $old->type !== $new->type ||
			$old->account_id !== $new->account_id ||
			$old->to_account_id !== $new->to_account_id ||
			!$old->amount->equals($new->amount);
	}

	/**
	 * Force update balance (useful for data correction)
	 */
	public function forceBalanceUpdate(Transaction $transaction): void
	{
		DB::transaction(function () use ($transaction) {
			$updater = $this->factory->getUpdater($transaction);
			$updater->apply($transaction);
		});
	}

	/**
	 * Batch delete transactions
	 */
	public function batchDeleteTransactions(array $transactionIds): void
	{
		DB::transaction(function () use ($transactionIds) {
			$transactions = $this->transactionRepo->findWhereIn(
				"id",
				$transactionIds
			);

			foreach ($transactions as $transaction) {
				$updater = $this->factory->getUpdater($transaction);
				$updater->revert($transaction);
				$transaction->delete();
			}
		});
	}

	/**
	 * Validate if transaction can be deleted (balance won't go negative)
	 */
	public function validateDeletion(
		Transaction $transaction,
		bool $checkNegativeBalance = true,
		bool $checkAccountStatus = true
	): bool {
		try {
			$this->validateBasicTransaction($transaction);

			if ($checkAccountStatus) {
				$this->validateAccountStatus($transaction);
			}

			if ($checkNegativeBalance) {
				$this->validateNegativeBalance($transaction);
			}

			return true;
		} catch (\Exception $e) {
			\Log::error("Transaction deletion validation failed.", [
				"transaction_id" => $transaction->id,
				"type" => $transaction->type->value,
				"error" => $e->getMessage(),
				"user_id" => auth()->id() ?? "Unknown",
			]);

			throw $e;
		}
	}

	private function validateBasicTransaction(Transaction $transaction): void
	{
		if (!$transaction->exists) {
			throw new \Exception("Transaction does not exist in database.");
		}

		if ($transaction->trashed()) {
			throw new \Exception("Transaction has been deleted.");
		}
	}

	/**
	 * Validate account statuses
	 */
	private function validateAccountStatus(Transaction $transaction): void
	{
		$accountRepo = app(AccountRepository::class);

		// Check source account
		$sourceAccount = $accountRepo->find($transaction->account_id);
		if (!$sourceAccount) {
			throw new \Exception(
				"Source account (ID: {$transaction->account_id}) not found."
			);
		}

		if (!$sourceAccount->is_active) {
			throw new \Exception(
				"Source account '{$sourceAccount->name}' is inactive."
			);
		}

		// Check destination account for transfers
		if (
			$transaction->type === TransactionType::TRANSFER &&
			$transaction->to_account_id
		) {
			$destAccount = $accountRepo->find($transaction->to_account_id);
			if (!$destAccount) {
				throw new \Exception(
					"Destination account (ID: {$transaction->to_account_id}) not found."
				);
			}

			if (!$destAccount->is_active) {
				throw new \Exception(
					"Destination account '{$destAccount->name}' is inactive."
				);
			}

			// Check if source and destination accounts are the same
			if ($transaction->account_id === $transaction->to_account_id) {
				throw new \Exception(
					"Source and destination accounts cannot be the same for transfers."
				);
			}
		}
	}

	/**
	 * Validate that deletion won't cause negative balances
	 */
	private function validateNegativeBalance(Transaction $transaction): void
	{
		$accountRepo = app(AccountRepository::class);
		$transactionAmount = $transaction->amount->getAmount()->toInt();

		switch ($transaction->type) {
			case TransactionType::INCOME:
				// Deleting INCOME: we subtract amount from account
				$account = $accountRepo->find($transaction->account_id);
				$currentBalance = $account->balance->getAmount()->toInt();
				$minimumBalance = $account->min_balance?->getAmount()->toInt() ?? 0;

				// Check if balance would go below minimum after deletion
				$projectedBalance = $currentBalance - $transactionAmount;

				if ($projectedBalance < $minimumBalance && !$account->allow_negative) {
					throw new \Exception(
						sprintf(
							"Deleting this income would cause account '%s' to have balance %s (min: %s). Current: %s",
							$account->name,
							$this->formatCurrency($projectedBalance, $account->currency),
							$this->formatCurrency($minimumBalance, $account->currency),
							$this->formatCurrency($currentBalance, $account->currency)
						)
					);
				}
				break;

			case TransactionType::EXPENSE:
				// Deleting EXPENSE: we add amount back to account
				// This increases balance, so no negative risk for source account
				// But we might want to check other rules
				break;

			case TransactionType::TRANSFER:
				// For transfer deletion:
				// - Source account: gets amount added back (no negative risk)
				// - Destination account: gets amount subtracted (check negative)

				if ($transaction->to_account_id) {
					$destAccount = $accountRepo->find($transaction->to_account_id);
					$destCurrentBalance = $destAccount->balance->getAmount()->toInt();
					$destMinBalance =
						$destAccount->min_balance?->getAmount()->toInt() ?? 0;

					// Projected balance after deleting transfer (subtract from destination)
					$destProjectedBalance = $destCurrentBalance - $transactionAmount;

					if (
						$destProjectedBalance < $destMinBalance &&
						!$destAccount->allow_negative
					) {
						throw new \Exception(
							sprintf(
								"Deleting this transfer would cause destination account '%s' to have balance %s (min: %s). Current: %s",
								$destAccount->name,
								$this->formatCurrency(
									$destProjectedBalance,
									$account->currency
								),
								$this->formatCurrency($destMinBalance, $account->currency),
								$this->formatCurrency($destCurrentBalance, $account->currency)
							)
						);
					}
				}
				break;
		}
	}

	/**
	 * Helper: Format currency amount
	 */
	private function formatCurrency(int $amount, ?string $currency = null): string
	{
		if (!is_numeric($amount)) {
			return 0;
		}

		return money($value, $currency);
	}
}
