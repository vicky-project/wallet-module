<?php

namespace Modules\Wallet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransactionRequest extends FormRequest
{
	public function authorize()
	{
		return true;
	}

	public function rules()
	{
		$rules = [
			"wallet_id" => "required|exists:finance_wallets,id",
			"type" => "required|in:deposit,withdraw",
			"amount" => "required|numeric|min:0.01",
			"category" => "required|string|max:100",
			"transaction_date" => "required|date",
			"payment_method" => "nullable|string|max:50",
			"reference_number" => "nullable|string|max:100",
			"description" => "required|string|max:500",
			"notes" => "nullable|string",
		];

		if ($this->isMethod("PUT") || $this->isMethod("PATCH")) {
			unset($rules["type"], $rules["wallet_id"], $rules["amount"]);
		}

		return $rules;
	}

	public function withValidator($validator)
	{
		$validator->after(function ($validator) {
			if ($this->type === "withdraw") {
				$wallet = \Modules\Wallet\Models\Wallet::find($this->wallet_id);
				if ($wallet && $wallet->balance < $this->amount) {
					$validator
						->errors()
						->add("amount", "Insufficient balance in wallet.");
				}
			}
		});
	}
}
