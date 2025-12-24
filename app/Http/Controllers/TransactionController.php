<?php

namespace Modules\Wallet\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Bavix\Wallet\Models\Wallet;
use Bavix\Wallet\Models\Transaction;
use Carbon\Carbon;
use Modules\Wallet\Models\Account;

class TransactionController extends Controller
{
	public function create(Request $request, Account $account, Wallet $wallet)
	{
		return view("wallet::wallets.create", compact("account", "wallet"));
	}

	public function deposit(Request $request, Account $account, Wallet $wallet)
	{
		$meta = [
			"description" => $request->description ?? null,
			"date" => $request->date_at ?? null,
		];
		$wallet->deposit((int) $request->amount);

		return redirect()
			->route("apps.wallet.wallets.show", [$account, $wallet])
			->with(
				"success",
				"Successful add deposit with amount: {$request->amount}"
			);
	}

	public function withdraw(Request $request, Account $account, Wallet $wallet)
	{
		// ...
	}
}
