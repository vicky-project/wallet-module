<?php

namespace Modules\Wallet\Http\Requests;

use Modules\Wallet\Constants\Permissions;
use Illuminate\Foundation\Http\FormRequest;

class AccountRequest extends FormRequest
{
	public function authorize()
	{
		return auth()->check() && Permissions::CREATE_ACCOUNTS;
	}

	public function rules()
	{
		$rules = [
			"name" => "required|string|max:255",
			"account_number" =>
				"nullable|string|max:50|unique:accounts,account_number," .
				$this->route("account"),
			"type" => "required|in:savings,checking,investment,general,credit",
			"description" => "nullable|string",
		];

		return $rules;
	}

	public function attributes()
	{
		return [
			"name" => "Account Name",
			"account_number" => "Account Number",
			"type" => "Account Type",
		];
	}
}
