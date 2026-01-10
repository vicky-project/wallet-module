<?php

namespace Modules\Wallet\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class BudgetAccount extends Pivot
{
	protected $table = "budget_accounts";

	protected $casts = [
		"created_at" => "datetime",
		"updated_at" => "datetime",
	];

	/**
	 * Relationship with budget
	 */
	public function budget()
	{
		return $this->belongsTo(Budget::class);
	}

	/**
	 * Relationship with account
	 */
	public function account()
	{
		return $this->belongsTo(Account::class);
	}
}
