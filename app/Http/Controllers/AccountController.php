<?php

namespace Modules\Wallet\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Wallet\Models\Account;
use Modules\Wallet\Repositories\AccountRepository;
use Modules\Wallet\Http\Requests\AccountRequest;

class AccountController extends Controller
{
	protected $accountRepository;

	public function __construct(AccountRepository $accountRepository)
	{
		$this->accountRepository = $accountRepository;
	}

	public function index()
	{
		$accounts = $this->accountRepository->getUserAccounts();
		dd($accounts);

		return view("wallet::accounts.index", compact("accounts"));
	}

	public function create()
	{
		return view("wallet::accounts.create");
	}

	public function store(AccountRequest $request)
	{
		try {
			$account = $this->accountRepository->createAccount($request->validated());

			return redirect()
				->route("apps.accounts.index")
				->with("success", "Account created successfully");
		} catch (\Exception $e) {
			return back()->withErrors($e->getMessage());
		}
	}

	public function show(Account $account)
	{
		$this->authorize("view", $account);

		$summary = $this->accountRepository->getAccountSummary($account);

		return response()->json([
			"success" => true,
			"data" => $summary,
		]);
	}

	public function update(AccountRequest $request, Account $account)
	{
		$this->authorize("update", $account);

		try {
			$account = $this->accountRepository->updateAccount(
				$account,
				$request->validated()
			);

			return response()->json([
				"success" => true,
				"message" => "Account updated successfully",
				"data" => $account,
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

	public function destroy(Account $account)
	{
		$this->authorize("delete", $account);

		try {
			$this->accountRepository->deleteAccount($account);

			return response()->json([
				"success" => true,
				"message" => "Account deleted successfully",
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

	public function summary(Account $account)
	{
		$this->authorize("view", $account);

		$summary = $this->accountRepository->getAccountSummary($account);

		return response()->json([
			"success" => true,
			"data" => $summary,
		]);
	}
}
