<?php

namespace Modules\VWallet\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Bavix\Wallet\Models\Wallet;
use Bavix\Wallet\Models\Transaction;
use Carbon\Carbon;
use Modules\Wallet\Models\Account;

class WalletController extends Controller
{
	public function deposit(Request $request, Account $account, Wallet $wallet)
	{
		// ...
	}

	public function withdraw(Request $request, Account $account, Wallet $wallet)
	{
		// ...
	}
}
