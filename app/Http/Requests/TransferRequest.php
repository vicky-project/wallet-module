<?php

namespace Modules\Wallet\Http\Requests;

use Modules\Wallet\Constants\Permissions;
use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
{
	public function authorize()
	{
		return auth()->check() &&
			auth()
				->user()
				->can(Permissions::TRANSFER_WALLETS);
	}

	public function rules()
	{
		return [
			"from_wallet_id" => "required|exists:wallets,id",
			"to_wallet_id" => "required|exists:wallets,id|different:from_wallet_id",
			"amount" => "required|numeric|min:0.01",
			"transaction_date" => "required|date",
			"payment_method" => "nullable|string|max:50",
			"reference_number" => "nullable|string|max:100",
			"description" => "nullable|string|max:500",
		];
	}

	public function withValidator($validator)
	{
		$validator->after(function ($validator) {
			$fromWallet = \Modules\Wallet\Models\Wallet::find($this->from_wallet_id);
			if ($fromWallet && $fromWallet->balance < $this->amount) {
				$validator
					->errors()
					->add("amount", "Insufficient balance in source wallet.");
			}
		});
	}
}
