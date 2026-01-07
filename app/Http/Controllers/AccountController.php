<?php

namespace Modules\Wallet\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Wallet\Models\Account;
use Modules\Wallet\Enums\TransactionType;
use Modules\Wallet\Services\AccountService;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Wallet\Http\Requests\AccountRequest;

class AccountController extends BaseController
{
	protected AccountService $service;

	public function __construct(AccountService $service)
	{
		$this->service = $service;
	}

	/**
	 * Display a listing of accounts
	 */
	public function index(Request $request)
	{
		try {
			$user = $request->user();
			$filters = $request->only(["type", "is_active", "search"]);

			$accounts = $this->service->getRepository->getUserAccounts(
				$user,
				$filters
			);
			$stats = $this->service->getAccountSummary($user);

			return view("wallet::accounts.index", compact("accounts", "stats"));
		} catch (\Exception $e) {
			logger()->error("Error to get resource account", [
				"message" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
			]);
			return redirect()
				->back()
				->withErrors($e->getMessage());
		}
	}

	/**
	 * Show the form for creating a new account
	 */
	public function create()
	{
		return view("wallet::accounts.create");
	}

	/**
	 * Store a newly created account
	 */
	public function store(AccountRequest $request)
	{
		try {
			$user = $request->user();
			$account = $this->service->createAccount($user, $request->validated());

			return redirect()
				->route("apps.accounts.index")
				->with("success", "Akun berhasil dibuat");
		} catch (\Exception $e) {
			return redirect()
				->back()
				->withInput()
				->withErrors($e->getMessage());
		}
	}

	/**
	 * Display the specified account
	 */
	public function show(Request $request, Account $account)
	{
		try {
			// Get recent transactions
			$transactions = $this->accountRepository->getRecentTransactions($account);

			// Calculate totals
			$incomeTotal = $account
				->transactions()
				->where("type", TransactionType::INCOME)
				->sum("amount");
			$expenseTotal = $account
				->transactions()
				->where("type", TransactionType::EXPENSE)
				->sum("amount");

			// Get balance history
			$balanceHistory = $this->accountRepository->getBalanceHistory($account);

			return view(
				"wallet::accounts.show",
				compact(
					"account",
					"transactions",
					"incomeTotal",
					"expenseTotal",
					"balanceHistory"
				)
			);
		} catch (\Exception $e) {
			logger()->error("Error showing account.", [
				"message" => $e->getMessage(),
				"trace" => $e->getTrace(),
			]);
			throw $e;
			return back()->withErrors($e->getMessage());
		}
	}

	/**
	 * Show the form for editing the account
	 */
	public function edit(Account $account)
	{
		try {
			return view("wallet::accounts.edit", compact("account"));
		} catch (\Exception $e) {
			return redirect()
				->back()
				->withErrors($e->getMessage());
		}
	}

	/**
	 * Update the specified account
	 */
	public function update(AccountRequest $request, Account $account)
	{
		try {
			$this->service->updateAccount($account, $request->validated());

			return redirect()
				->route("apps.accounts.show", $account)
				->with("success", "Akun berhasil diperbarui");
		} catch (\Exception $e) {
			return redirect()
				->back()
				->withInput()
				->withErrors($e->getMessage());
		}
	}

	/**
	 * Remove the specified account
	 */
	public function destroy(Account $account)
	{
		try {
			$this->authorize("delete", $account);

			// Check if account has transactions
			if ($account->transactions()->exists()) {
				return redirect()
					->back()
					->withErrors("Akun tidak dapat dihapus karena memiliki transaksi");
			}

			$account->delete();

			return redirect()
				->route("apps.accounts.index")
				->with("success", "Akun berhasil dihapus");
		} catch (\Exception $e) {
			return redirect()
				->back()
				->withErrors($e->getMessage());
		}
	}

	/**
	 * Toggle account default status
	 */
	public function toggleDefault(Account $account)
	{
		try {
			$this->authorize("update", $account);

			// Set all accounts to non-default
			Account::where("user_id", auth()->id())
				->where("id", "!=", $account->id)
				->update(["is_default" => false]);

			// Toggle this account
			$account->is_default = !$account->is_default;
			$account->save();

			$status = $account->is_default
				? "diatur sebagai default"
				: "dinonaktifkan sebagai default";

			return redirect()
				->back()
				->with("success", "Akun berhasil {$status}");
		} catch (\Exception $e) {
			return redirect()
				->back()
				->withErrors(["error" => $e->getMessage()]);
		}
	}

	/**
	 * Get accounts for dropdown (API)
	 */
	public function dropdown()
	{
		try {
			$accounts = $this->accountRepository->getForDropdown(auth()->user());

			return response()->json([
				"success" => true,
				"data" => $accounts,
			]);
		} catch (\Exception $e) {
			return response()->json(
				[
					"success" => false,
					"message" => $e->getMessage(),
				],
				500
			);
		}
	}
}
