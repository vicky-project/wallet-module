<?php

namespace Modules\Wallet\Repositories;

use Carbon\Carbon;
use App\Models\User;
use Brick\Money\Money;
use Modules\Wallet\Models\Account;
use Modules\Wallet\Models\Transfer;

class TransferRepository extends BaseRepository
{
	public function __construct(Transfer $model)
	{
		parent::__construct($model);
	}

	/**
	 * Create transfer with Money amounts
	 */
	public function createTransfer(array $data, User $user): Transfer
	{
		$data["user_id"] = $user->id;

		// Convert amounts to Money
		$data["amount"] = $this->toDatabaseAmount($this->toMoney($data["amount"]));

		if (isset($data["fee"])) {
			$data["fee"] = $this->toDatabaseAmount($this->toMoney($data["fee"]));
		} else {
			$data["fee"] = 0;
		}

		// Set default transfer date
		if (!isset($data["transfer_date"])) {
			$data["transfer_date"] = Carbon::now();
		}

		$transfer = $this->create($data);

		// Execute the transfer (update account balances)
		$this->executeTransfer($transfer);

		return $transfer;
	}

	/**
	 * Execute transfer between accounts
	 */
	private function executeTransfer(Transfer $transfer): void
	{
		$fromAccount = Account::find($transfer->from_account_id);
		$toAccount = Account::find($transfer->to_account_id);

		if (!$fromAccount || !$toAccount) {
			throw new \Exception("Invalid account(s)");
		}

		$transferAmount = $this->fromDatabaseAmount($transfer->amount);
		$feeAmount = $this->fromDatabaseAmount($transfer->fee);
		$netAmount = $transferAmount->minus($feeAmount);

		// Check if source account has sufficient balance
		$sourceBalance = $this->fromDatabaseAmount($fromAccount->current_balance);
		if ($sourceBalance->isLessThan($transferAmount)) {
			throw new \Exception("Insufficient balance in source account");
		}

		// Perform transfer in transaction
		\DB::transaction(function () use (
			$fromAccount,
			$toAccount,
			$transferAmount,
			$netAmount
		) {
			// Deduct from source account
			$fromAccount->current_balance = $this->toDatabaseAmount(
				$this->fromDatabaseAmount($fromAccount->current_balance)->minus(
					$transferAmount
				)
			);
			$fromAccount->save();

			// Add to destination account (minus fee)
			$toAccount->current_balance = $this->toDatabaseAmount(
				$this->fromDatabaseAmount($toAccount->current_balance)->plus($netAmount)
			);
			$toAccount->save();
		});
	}

	/**
	 * Reverse a transfer
	 */
	public function reverseTransfer(int $transferId): bool
	{
		$transfer = $this->find($transferId);

		$fromAccount = Account::find($transfer->from_account_id);
		$toAccount = Account::find($transfer->to_account_id);

		$transferAmount = $this->fromDatabaseAmount($transfer->amount);
		$feeAmount = $this->fromDatabaseAmount($transfer->fee);
		$netAmount = $transferAmount->minus($feeAmount);

		\DB::transaction(function () use (
			$fromAccount,
			$toAccount,
			$transferAmount,
			$netAmount
		) {
			// Return to source account
			$fromAccount->current_balance = $this->toDatabaseAmount(
				$this->fromDatabaseAmount($fromAccount->current_balance)->plus(
					$transferAmount
				)
			);
			$fromAccount->save();

			// Deduct from destination account
			$toAccount->current_balance = $this->toDatabaseAmount(
				$this->fromDatabaseAmount($toAccount->current_balance)->minus(
					$netAmount
				)
			);
			$toAccount->save();
		});

		return $this->delete($transferId);
	}

	/**
	 * Get transfers by account
	 */
	public function getByAccount(Account $account): Collection
	{
		return $this->model
			->where("from_account_id", $account->id)
			->orWhere("to_account_id", $account->id)
			->orderBy("transfer_date", "desc")
			->get()
			->map(function ($transfer) use ($account) {
				$transfer->formatted_amount = $this->formatMoney(
					$this->fromDatabaseAmount($transfer->amount)
				);
				$transfer->formatted_fee = $this->formatMoney(
					$this->fromDatabaseAmount($transfer->fee)
				);
				$transfer->net_amount = $this->formatMoney(
					$this->fromDatabaseAmount($transfer->amount)->minus(
						$this->fromDatabaseAmount($transfer->fee)
					)
				);
				$transfer->is_outgoing = $transfer->from_account_id == $account->id;
				return $transfer;
			});
	}
}
