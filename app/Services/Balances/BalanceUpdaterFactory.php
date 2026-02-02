<?php

namespace Modules\Wallet\Services\Balances;

use Modules\Wallet\Models\Transaction;
use Modules\Wallet\Enums\TransactionType;
use Modules\Wallet\Interfaces\BalanceOperationInterface;
use Modules\Wallet\Services\Balances\Types\{
	IncomeBalanceUpdater,
	ExpenseBalanceUpdater,
	TransferBalanceUpdater
};
use InvalidArgumentException;

class BalanceUpdaterFactory
{
	private array $updaters = [];

	public function __construct()
	{
		$this->updaters = [
			TransactionType::INCOME => IncomeBalanceUpdater::class,
			TransactionType::EXPENSE => ExpenseBalanceUpdater::class,
			TransactionType::TRANSFER => TransferBalanceUpdater::class,
		];
	}

	public function getUpdater(
		Transaction $transaction
	): BalanceOperationInterface {
		$type = $transaction->type;

		if (!isset($this->updaters[$type])) {
			throw new InvalidArgumentException(
				"No balance updater found for transaction type: {$type}"
			);
		}

		$updaterClass = $this->updaters[$type];
		return app($updaterClass);
	}

	public function getUpdaterByType(
		TransactionType $type
	): BalanceOperationInterface {
		if (!isset($this->updaters[$type->value])) {
			throw new InvalidArgumentException(
				"No balance updater found for transaction type: {$type->value}"
			);
		}

		$updaterClass = $this->updaters[$type->value];
		return app($updaterClass);
	}
}
