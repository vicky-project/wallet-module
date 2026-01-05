<?php

namespace Modules\Wallet\Http\Requests;

use Modules\Wallet\Enums\TransactionType;
use Modules\Wallet\Constants\Permissions;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class TransactionRequest extends FormRequest
{
	public function authorize()
	{
		return auth()->check() &&
			($this->isMethod("PUT")
				? auth()
					->user()
					->can(Permissions::EDIT_TRANSACTIONS)
				: auth()
					->user()
					->can(Permissions::CREATE_TRANSACTIONS));
	}

	public function rules()
	{
		$rules = [
			"account_id" => "required|exists:accounts,id",
			"type" => ["required", Rule::enum(TransactionType::class)],
			"title" => "required|string|max:500",
			"amount" => "required|numeric|min:0.01",
			"category_id" => "required|exists:categories,id",
			"transaction_date" => "required|date",
			"payment_method" => "nullable|string|max:50",
			"reference_number" => "nullable|string|max:100",
			"description" => "nullable|string|max:5000",
			"is_recurring" => "nullable",
			"recurring_period" => [
				Rule::requiredIf(!is_null($this->is_recurring)),
				"string",
			],
			"recurring_end_date" => "nullable|date",
		];

		if ($this->isMethod("PUT") || $this->isMethod("PATCH")) {
			unset($rules["type"], $rules["account_id"], $rules["amount"]);
		}

		return $rules;
	}

	public function withValidator($validator)
	{
		$validator->after(function ($validator) {
			if ($this->type === TransactionType::EXPENSE) {
				$account = \Modules\Wallet\Models\Account::find($this->account_id);
				if ($account && $account->current_balance < $this->amount) {
					$validator
						->errors()
						->add("amount", "Insufficient balance in account.");
				}
			}
		});
	}
}
