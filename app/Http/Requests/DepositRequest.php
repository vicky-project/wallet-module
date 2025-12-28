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
			"deposit-category" => "required|exists:categories,name",
			"deposit-amount" => "required|numeric|min:0.01",
			"deposit-description" => "nullable|string|max:500",
			"deposit-date_at" => "sometimes|date",
		];
	}

	public function attributes()
	{
		return [
			"deposit-amount" => "Amount",
			"deposit-description" => "Description",
			"deposit-date_at" => "Date",
		];
	}
}
