<?php

namespace Modules\Wallet\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Wallet\Enums\AccountType;
use Modules\Wallet\Constants\Permissions;
use Illuminate\Foundation\Http\FormRequest;

class AccountRequest extends FormRequest
{
	public function authorize()
	{
		return auth()->check() &&
			(auth()
				->user()
				->can(Permissions::CREATE_ACCOUNTS) ||
				auth()
					->user()
					->can(Permissions::EDIT_ACCOUNTS));
	}

	public function rules()
	{
		$rules = [
			"name" => "required|string|max:255",
			"account_number" =>
				"nullable|string|max:50|unique:accounts,account_number," .
				$this->route("account"),
			"type" => ["required", Rule::enum(AccountType::class)],
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
