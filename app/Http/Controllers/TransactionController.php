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
		dd($wallet->with(["transactions"])->get(), $request->all());
	}

	public function withdraw(Request $request, Account $account, Wallet $wallet)
	{
		// ...
	}
}
