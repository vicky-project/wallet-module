<?php
namespace Modules\Wallet\Services\Balances\Types;

use Modules\Wallet\Models\Transaction;
use Modules\Wallet\Services\Balances\AbstractBalanceUpdater;

class TransferBalanceUpdater extends AbstractBalanceUpdater
{
	public function apply(Transaction $transaction): void
	{
		$amount = $transaction->amount->getAmount()->toInt();

		// Transfer mengurangi dari akun sumber
		$this->updateBalance($transaction->account_id, $amount, false);

		// Transfer menambah ke akun tujuan
		if ($transaction->to_account_id) {
			$this->updateBalance($transaction->to_account_id, $amount, true);
		}
	}

	public function revert(Transaction $transaction): void
	{
		$amount = $transaction->amount->getAmount()->toInt();

		// Revert transfer: tambah kembali ke akun sumber
		$this->updateBalance($transaction->account_id, $amount, true);

		// Revert transfer: kurangi dari akun tujuan
		if ($transaction->to_account_id) {
			$this->updateBalance($transaction->to_account_id, $amount, false);
		}
	}
}
