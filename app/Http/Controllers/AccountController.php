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
		return view("wallet::accounts.create");
	}

	public function store(Request $request)
	{
		$request->validate([
			"name" => "required|string|max:255",
			"type" => "required|string",
			"description" => "nullable|string",
		]);

		$account = Account::create([
			"user_id" => Auth::id(),
			"name" => $request->name,
			"type" => $request->type,
			"description" => $request->description,
			"is_active" => (bool) $request->is_active,
		]);

		return redirect()
			->route("apps.wallet.index")
			->with("success", "Account created successfully.");
	}

	public function show(Account $account)
	{
		$wallets = $account
			->wallets()
			->get()
			->map(fn($wallet) => $wallet->withCount("transactions"));
		dd($wallets);

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
			"wallet::accounts.show",
			compact("account", "wallets", "total", "currencies")
		);
	}

	public function edit(Account $account)
	{
		return view("wallet::accounts.edit", compact("account"));
	}

	public function update(Request $request, Account $account)
	{
		$request->validate([
			"name" => "required|string|max:255",
			"type" => "required|string",
			"description" => "nullable|string",
		]);

		$account->update([
			"name" => $request->name,
			"type" => $request->type,
			"description" => $request->description,
		]);

		return redirect()
			->route("apps.wallet.index")
			->with("success", "Edit account for: {$account->name} successfuly.");
	}
}
