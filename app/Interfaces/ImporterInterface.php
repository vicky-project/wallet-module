<?php

namespace Modules\Financial\Interfaces;

use Modules\Wallet\Models\Account;

interface ImporterInterface
{
	public function __construct(array $data, Account $account);

	public function load(): array;
}
