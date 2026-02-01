<?php
namespace Modules\Wallet\Services\Balances;

use Modules\Wallet\Interfaces\BalanceOperationInterface;
use Modules\Wallet\Repositories\AccountRepository;

abstract class AbstractBalanceUpdater implements BalanceOperationInterface
{
	protected AccountRepository $accountRepo;

	public function __construct()
	{
		$this->accountRepo = app(AccountRepository::class);
	}

	protected function updateBalance(
		int $accountId,
		int $amount,
		bool $increment
	): void {
		$this->accountRepo->updateBalance($accountId, $amount, $increment);
	}
}
