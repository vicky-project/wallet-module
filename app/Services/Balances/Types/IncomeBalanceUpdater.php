<?php

namespace Modules\Wallet\Services\Balances\Types;

use Modules\Wallet\Models\Transaction;
use Modules\Wallet\Services\Balances\AbstractBalanceUpdater;

class IncomeBalanceUpdater extends AbstractBalanceUpdater
{
	public function apply(Transaction $transaction): void
	{
		// Income menambah saldo
		$this->updateBalance(
			$transaction->account_id,
			$transaction->amount->getAmount()->toInt(),
			true
		);
	}

	public function revert(Transaction $transaction): void
	{
		// Revert income mengurangi saldo
		$this->updateBalance(
			$transaction->account_id,
			$transaction->amount->getAmount()->toInt(),
			false
		);
	}
}
