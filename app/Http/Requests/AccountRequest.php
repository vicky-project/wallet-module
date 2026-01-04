<?php

namespace Modules\Wallet\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Wallet\Enums\WalletType;
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
			"type" => ["required", Rule::enum(AccountType::class)],
			"account_number" => "nullable|numeric|max:10",
			"bank_name" => "nullable|string|max:500",
			"initial_balance" => "nullable|min:0",
			"current_balance" => "nullable|min:0",
			"is_default" => "boolean",
		];

		// For update, initial_balance is not required
		if ($this->isMethod("PUT") || $this->isMethod("PATCH")) {
			unset($rules["initial_balance"]);
		}

		return $rules;
	}

	public function attributes()
	{
		return [
			"name" => "Wallet Name",
			"type" => "Wallet Type",
			"account_number" => "Account Number",
			"bank_name" => "Bank Name",
			"initial_balance" => "Initial Balance",
			"current_balance" => "Current Balance",
			"is_default" => "As Default",
		];
	}
}
