<?php

namespace Modules\Wallet\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Wallet\Constants\Permissions;
use Illuminate\Foundation\Http\FormRequest;

class WithdrawalRequest extends FormRequest
{
	public function authorize()
	{
		return auth()->check() &&
			auth()
				->user()
				->can(Permissions::WITHDRAW_WALLETS);
	}

	public function rules()
	{
		return [
			"withdraw-category" => "required|exists:categories,name",
			"withdraw-amount" => "required|numeric|min:0.01",
			"withdraw-description" => "nullable|string|max:500",
			"withdraw-date_at" => "sometimes|date",
		];
	}

	public function attributes()
	{
		return [
			"withdraw-amount" => "Amount",
			"withdraw-description" => "Description",
			"withdraw-date_at" => "Date",
		];
	}
}
