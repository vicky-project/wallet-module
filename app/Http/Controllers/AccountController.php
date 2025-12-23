<?php

namespace Modules\Wallet\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Wallet\Models\Account;
use Modules\Wallet\Constants\Permissions;

class AccountController extends Controller
{
	public function __construct()
	{
		$this->middleware("permission:" . Permissions::VIEW_ACCOUNTS)->only([
			"index",
			"show",
		]);
	}

	public function index()
	{
		$accounts = Account::where("user_id", Auth::id())->get();
		return view("wallet::accounts.index", compact("accounts"));
	}

	public function create()
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
		return view("wallet::accounts.create", compact("currencies"));
	}

	public function store(Request $request)
	{
		$request->validate([
			"name" => "required|string|max:255",
			"type" => "required|string",
			"description" => "nullable|string",
			"currency" => "required|string|size:3",
		]);

		$account = Account::create([
			"user_id" => Auth::id(),
			"name" => $request->name,
			"type" => $request->type,
			"description" => $request->description,
			"currency" => $request->currency,
			"is_active" => (bool) $request->is_active,
		]);

		return redirect()
			->route("apps.wallet.index")
			->with("success", "Account created successfully.");
	}

	public function show(Account $account)
	{
		dd($account->wallets()->get());
		$wallets = $account
			->wallets()
			->with("transactions")
			->get();

		return view("wallet::accounts.show", compact("account", "wallets"));
	}
}
