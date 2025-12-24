<?php

namespace Modules\Wallet\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\BaseController;
use Illuminate\Support\Facades\Auth;
use Bavix\Wallet\Models\Wallet;
use Bavix\Wallet\Models\Transaction;
use Carbon\Carbon;
use Modules\Wallet\Models\Account;
use Modules\Wallet\Constants\Permissions;

class WalletController extends BaseController
{
	public function __construct()
	{
		if ($this->isPermissionMiddlewareExists()) {
			$this->middleware("permission:" . Permissions::VIEW_WALLETS)->only([
				"show",
			]);
		}
		$this->middleware("permission:" . Permissions::CREATE_WALLETS)->only([
			"createWallet",
			"store",
		]);
	}

	public function show(Account $account, Wallet $wallet)
	{
		// Get transactions grouped by period (month)
		$transactions = $wallet
			->transactions()
			->orderBy("created_at", "desc")
			->get()
			->groupBy(function ($transaction) {
				return Carbon::parse($transaction->created_at)->format("F Y");
			})
			->map(function ($transaction) {
				return [
					"total" => $transaction->count(),
					"deposit" => $transaction
						->filter(fn($item) => $item->type === "deposit")
						->count(),
					"withdraw" => $transaction
						->filter(fn($item) => $item->type === "withdraw")
						->count(),
				];
			});

		return view(
			"wallet::wallets.show",
			compact("account", "wallet", "transactions")
		);
	}

	public function store(Request $request, Account $account)
	{
		$request->validate([
			"name" => "required|string|max:255",
		]);

		$meta = [
			"currency" => $request->currency ?? "",
			"initial_balance" => $request->initial_balance ?? 0,
			"created_by" => auth()->id(),
		];

		$wallet = $account->createWallet([
			"name" => $request->name,
			"description" => $request->description ?? null,
			"balance" => $request->initial_balance ?? 0,
			"meta" => $meta,
		]);

		return redirect()
			->route("apps.wallet.show", $account)
			->with("success", "Wallet created successfully.");
	}

	public function edit(Request $request, Account $account, Wallet $wallet)
	{
		$currencies = collect(config("money.currencies"))
			->keys()
			->mapWithKeys(
				fn($currency) => [
					$currency =>
						config("money.currencies")[$currency]["name"] .
						" (" .
						config("money.currencies")[$currency]["symbol"] .
						")",
				]
			)
			->toArray();
		return view(
			"wallet::wallets.edit",
			compact("account", "wallet", "currencies")
		);
	}

	public function refreshBalance(
		Request $request,
		Account $account,
		Wallet $wallet
	) {
		$wallet->refreshBalance();

		return back()->with("success", "Balance updated.");
	}

	public function transfer(Request $request, Account $account, Wallet $wallet)
	{
		$request->validate([
			"to_wallet_id" => "required|exists:wallets,id",
			"amount" => "required|numeric|min:0.01",
			"description" => "nullable|string",
		]);

		$toWallet = Wallet::find($request->to_wallet_id);

		$transfer = $wallet->transfer($toWallet, $request->amount, [
			"description" => $request->description,
		]);

		if ($transfer) {
			return back()->with("success", "Transfer successful.");
		}

		return back()->withErrors("Transfer failed.");
	}

	public function showImportForm(Account $account, Wallet $wallet)
	{
		$this->authorize("view", $account);

		return view("wallet::wallets.import", compact("account", "wallet"));
	}

	public function import(Request $request, Account $account, Wallet $wallet)
	{
		$request->validate([
			"file" => "required|file|mimes:csv,xlsx,xls",
		]);

		// TODO: Implement your import class logic here
		// Example: (new TransactionImport($wallet))->process($request->file('file'));

		return back()->with("success", "File uploaded successfully. Processing...");
	}
}
