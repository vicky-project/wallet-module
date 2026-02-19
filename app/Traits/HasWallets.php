<?php

namespace Modules\Wallet\Traits;

use Modules\Wallet\Models\Account;
use Modules\Wallet\Models\Category;
use Modules\Wallet\Models\Transaction;
use Modules\Wallet\Models\Budget;
use Modules\Wallet\Models\SavingGoal;
use Modules\Wallet\Enums\TransactionType;

trait HasWallets
{
	public function categories()
	{
		return $this->hasMany(Category::class);
	}

	public function budgets()
	{
		return $this->hasMany(Budget::class);
	}

	public function savingGoals()
	{
		return $this->hasMany(SavingGoal::class);
	}

	public function accounts()
	{
		return $this->hasMany(Account::class);
	}

	public function transactions()
	{
		return $this->hasMany(Transaction::class);
	}

	public function getTotalBalanceAttribute()
	{
		return $this->accounts()->sum("current_balance");
	}

	public function getMonthlyIncome($month = null, $year = null)
	{
		$month = $month ?? date("m");
		$year = $year ?? date("Y");

		return $this->transactions()
			->where("type", TransactionType::INCOME)
			->whereMonth("transaction_date", $month)
			->whereYear("transaction_date", $year)
			->sum("amount");
	}

	public function getMonthlyExpense($month = null, $year = null)
	{
		$month = $month ?? date("m");
		$year = $year ?? date("Y");

		return $this->transactions()
			->where("type", TransactionType::EXPENSE)
			->whereMonth("transaction_date", $month)
			->whereYear("transaction_date", $year)
			->sum("amount");
	}
}
