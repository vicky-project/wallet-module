<?php

namespace Modules\Wallet\Interfaces;

use Brick\Money\Money;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Models\Transaction;

interface TransactionServiceInterface
{
	public function deposit(Wallet $wallet, array $data): Transaction;

	public function withdraw(Wallet $wallet, array $data): Transaction;

	public function transfer(
		Wallet $fromWallet,
		Wallet $toWallet,
		array $data
	): array;

	public function recordTransaction(array $data): Transaction;

	public function updateTransactionStatus(
		Transaction $transaction,
		string $status,
		array $data = []
	): Transaction;

	public function getWalletBalance(Wallet $wallet, ?string $date = null): Money;

	public function getAccountBalance(
		int $accountId,
		?string $date = null
	): Money;
}
