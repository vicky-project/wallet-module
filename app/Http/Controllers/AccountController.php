<?php

namespace Modules\Wallet\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Wallet\Models\Account;
use Modules\Wallet\Enums\TransactionType;
use Modules\Wallet\Services\AccountService;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Wallet\Http\Requests\AccountRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Modules\Wallet\Http\Resources\AccountResource;
use Symfony\Component\HttpFoundation\Response;

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

			$accounts = $this->service
				->getRepository()
				->getUserAccounts($user, $filters);
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
			$this->service->validateAccount($account, $request->user());
			$account->loadCount("transactions");

			return view("wallet::accounts.show", compact("account"));
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
	public function setDefault(Request $request, Account $account)
	{
		$user = $request->user();
		try {
			// Set all accounts to non-default
			Account::where("user_id", $user)
				->where("id", "!=", $account->id)
				->update(["is_default" => false]);

			// Toggle this account
			$account->is_default = !$account->is_default;
			$account->save();

			$status = $account->is_default
				? "diatur sebagai default"
				: "dinonaktifkan sebagai default";

			return back()->with("success", "Akun berhasil {$status}");
		} catch (\Exception $e) {
			return back()->withErrors(["error" => $e->getMessage()]);
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

	/**
	 * Recalculate account balance.
	 */
	public function recalculateBalance(
		Request $request,
		Account $account
	): JsonResponse|RedirectResponse {
		try {
			$this->service->validateAccount($account, $request->user());
			$this->service->recalculateBalance($account);

			return back()->with(
				"success",
				"Account balance recalculated successfully"
			);

			/* return response()->json([
				"success" => true,
				"message" => "Account balance recalculated successfully",
				"data" => new AccountResource($account->fresh()),
			]); */
		} catch (\Exception $e) {
			return response()->json(
				[
					"success" => false,
					"message" => "Failed to recalculate balance",
					"error" => $e->getMessage(),
				],
				Response::HTTP_BAD_REQUEST
			);
		}
	}

	public function recalculateAllBalance(Request $request)
	{
		$user = $request->user();
		try {
			if ($this->service->bulkRecalculateBalances($user)) {
				return back()->with("success", "Balance updated successfuly");
			}

			return back()->withErrors("Failed to recalculate all balance");
		} catch (\Exception $e) {
			logger()->error("Failed to recalculate all accounts", [
				"message" => $e->getMessage(),
				"trace" => $e->getTrace(),
			]);

			return back()->withErors($e->getMessage);
		}
	}
}
