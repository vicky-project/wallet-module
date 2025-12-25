<?php

namespace Modules\Wallet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WalletRequest extends FormRequest
{
	public function authorize()
	{
		return true;
	}

	public function rules()
	{
		$rules = [
			"name" => "required|string|max:255",
			"type" => "required|in:cash,bank,digital,investment,credit",
			"initial_balance" => "required|numeric|min:0",
			"currency" => "required|string|size:3",
			"is_active" => "boolean",
			"is_default" => "boolean",
			"description" => "nullable|string|max:500",
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
			"initial_balance" => "Initial Balance",
			"currency" => "Currency",
		];
	}
}
