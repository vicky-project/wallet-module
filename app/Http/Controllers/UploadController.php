<?php
namespace Modules\Wallet\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Wallet\Models\Account;
use Modules\Wallet\Models\Transaction;

class UploadController extends Controller
{
	public function index(Request $request)
	{
		$user = $request->user();
		$accounts = Account::forUser($user->id)
			->active()
			->get();

		return view("wallet::upload", compact("accounts"));
	}

	public function upload(Request $request)
	{
	}
}
