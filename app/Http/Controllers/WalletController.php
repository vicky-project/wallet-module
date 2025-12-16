<?php

namespace Modules\Wallet\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Bavix\Wallet\Models\Wallet;
use Bavix\Wallet\Models\Transaction;
use Carbon\Carbon;
use Modules\Wallet\Models\Account;

class WalletController extends Controller
{
	public function show(Account $account, Wallet $wallet)
	{
		$this->authorize("view", $account);

		// Get transactions grouped by period (month)
		$transactions = $wallet
			->transactions()
			->orderBy("created_at", "desc")
			->get()
			->groupBy(function ($transaction) {
				return Carbon::parse($transaction->created_at)->format("F Y");
			});

		return view(
			"wallet::wallets.show",
			compact("account", "wallet", "transactions")
		);
	}

	public function createWallet(Request $request, Account $account)
	{
		$request->validate([
			"name" => "required|string|max:255",
			"slug" => "required|string|unique:wallets,slug",
		]);

		$wallet = $account->createWallet([
			"name" => $request->name,
			"slug" => $request->slug,
		]);

		return redirect()
			->route("wallet.accounts.show", $account)
			->with("success", "Wallet created successfully.");
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
