<?php

namespace Modules\Wallet\Models;

use Bavix\Wallet\Interfaces\Wallet;
use Bavix\Wallet\Traits\HasWallet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Bavix\Wallet\Models\Wallet as WalletModel;

class Account extends Model implements Wallet
{
	use HasWallet;

	protected $fillable = [
		"user_id",
		"name",
		"type",
		"description",
		"currency",
		"is_active",
	];

	public function user(): BelongsTo
	{
		return $this->belongsTo(config("auth.providers.users.model"));
	}
}
