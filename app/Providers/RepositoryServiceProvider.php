<?php

namespace Modules\Wallet\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Wallet\Repositories\{
	CategoryRepository,
	TransactionRepository,
	BudgetRepository,
	SavingGoalRepository,
	TransferRepository,
	AccountRepository
};
use Modules\Wallet\Models\{
	Category,
	Transaction,
	Budget,
	SavingGoal,
	Transfer,
	Account
};

class RepositoryServiceProvider extends ServiceProvider
{
	/**
	 * Register services.
	 */
	public function register(): void
	{
		$this->app->bind(CategoryRepository::class, function ($app) {
			return new CategoryRepository(new Category());
		});

		$this->app->bind(TransactionRepository::class, function ($app) {
			return new TransactionRepository(new Transaction());
		});

		$this->app->bind(BudgetRepository::class, function ($app) {
			return new BudgetRepository(new Budget());
		});

		$this->app->bind(SavingGoalRepository::class, function ($app) {
			return new SavingGoalRepository(new SavingGoal());
		});

		$this->app->bind(TransferRepository::class, function ($app) {
			return new TransferRepository(new Transfer());
		});

		$this->app->bind(AccountRepository::class, function ($app) {
			return new AccountRepository(new Account());
		});
	}

	/**
	 * Bootstrap services.
	 */
	public function boot(): void
	{
		//
	}
}
