<?php

namespace Modules\Wallet\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Wallet\Constants\Permissions;
use Illuminate\Foundation\Http\FormRequest;

class DepositRequest extends FormRequest
{
	public function authorize()
	{
		return auth()->check() &&
			auth()
				->user()
				->can(Permissions::DEPOSIT_WALLETS);
	}

	public function rules()
	{
		return [
			"amount" => "required|numeric|min:0.01",
			"description" => "nullable|string|max:500",
			"date_at" => "sometimes|date",
		];
	}

	public function attributes()
	{
		return [
			"amount" => "Amount",
			"description" => "Description",
			"date_at" => "Date",
		];
	}
}
