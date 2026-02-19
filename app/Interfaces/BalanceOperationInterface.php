<?php

namespace Modules\Wallet\Interfaces;

use Modules\Wallet\Models\Transaction;

interface BalanceOperationInterface
{
	public function apply(Transaction $transaction): void;
	public function revert(Transaction $transaction): void;
}
