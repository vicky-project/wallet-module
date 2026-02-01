<?php
namespace Modules\Wallet\Services\Balances\Types;

use Modules\Wallet\Models\Transaction;
use Modules\Wallet\Services\Balances\AbstractBalanceUpdater;

class ExpenseBalanceUpdater extends AbstractBalanceUpdater
{
	public function apply(Transaction $transaction): void
	{
		// Expense mengurangi saldo
		$this->updateBalance(
			$transaction->account_id,
			$transaction->amount->getAmount()->toInt(),
			false
		);
	}

	public function revert(Transaction $transaction): void
	{
		// Revert expense menambah saldo
		$this->updateBalance(
			$transaction->account_id,
			$transaction->amount->getAmount()->toInt(),
			true
		);
	}
}
