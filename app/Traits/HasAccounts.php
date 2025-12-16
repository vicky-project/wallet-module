<?php

namespace Modules\Wallet\Traits;

use Modules\Wallet\Models\Account;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasAccounts
{
	public function accounts(): HasMany
	{
		return $this->hasMany(Account::class, "user_id");
	}
}
