<?php
namespace Modules\Wallet\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TelegramMController extends Controller
{
	public function index(Request $request)
	{
		return view("wallet::mini-apps.index");
	}

	public function handleData(Request $request)
	{
		$initData = $request->input("initData");
		return response()->json($initData);
	}
}
