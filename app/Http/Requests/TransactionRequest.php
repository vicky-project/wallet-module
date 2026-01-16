<?php

namespace Modules\Wallet\Http\Requests;

use Modules\Wallet\Enums\TransactionType;
use Modules\Wallet\Enums\RecurringFreq;
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
			"category_id" => "required|exists:categories,id",
			"type" => ["required", Rule::enum(TransactionType::class)],
			"amount" => "required|integer|min:1",
			"description" => "required|string|max:255",
			"transaction_date" => "required|date",
			"notes" => "nullable|string",
			"payment_method" => "nullable|string",
			"reference_number" => "nullable|string|max:100",
			"is_recurring" => "nullable|boolean",
		];

		if ($this->type === TransactionType::TRANSFER->value) {
			$rules["to_account_id"] =
				"required|exists:accounts,id|different:account_id";
		}

		if ($this->boolean("is_recurring")) {
			$rules["frequency"] = ["required", Rule::enum(RecurringFreq::class)];
			$rules["interval"] = "integer|min:1";
			$rules["start_date"] = "required|date";
			$rules["end_date"] = "nullable|date|after:start_date";
			$rules["remaining_occurrences"] = "nullable|integer|min:0";

			$frequency = $this->input("frequency");

			if ($frequency === RecurringFreq::WEEKLY->value) {
				$rules["day_of_week"] = "nullable|integer|between:0,6";
			}

			if (
				in_array($frequency, [
					RecurringFreq::MONTHLY->value,
					RecurringFreq::QUARTERLY->value,
				])
			) {
				$rules["day_of_month"] = "nullable|integer|between:1,31";
			}

			if ($frequency === RecurringFreq::CUSTOM->value) {
				$rules["custom_schedule"] = "required|array|min:1";
				$rules["custom_schedule.*"] = "date";
			}
		}

		if ($this->isMethod("PUT") || $this->isMethod("PATCH")) {
			unset($rules["account_id"]);
		}

		return $rules;
	}

	public function withValidator($validator)
	{
		$validator->after(function ($validator) {
			if ($this->type === TransactionType::EXPENSE) {
				$account = \Modules\Wallet\Models\Account::find($this->account_id);
				if (
					$account &&
					$account->balance->getAmount()->toInt() < $this->amount
				) {
					$validator
						->errors()
						->add("amount", "Insufficient balance in account.");
				}
			}
		});
	}
}
