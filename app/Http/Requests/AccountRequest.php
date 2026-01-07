<?php

namespace Modules\Wallet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Wallet\Enums\AccountType;
use Illuminate\Validation\Rule;
use Modules\Wallet\Constants\Permissions;

class AccountRequest extends FormRequest
{
	public function authorize(): bool
	{
		return auth()->check() &&
			(auth()
				->user()
				->can(Permissions::CREATE_ACOUNTS) ||
				auth()
					->user()
					->can(Permissions::EDIT_ACCOUNTS));
	}

	public function rules(): array
	{
		$rules = [
			"name" => "required|string|max:255",
			"type" => ["required", Rule::enum(AccountType::class)],
			"initial_balance" => "nullable|numeric",
			"currency" => "nullable|string|size:3",
			"account_number" => "nullable|string|max:100",
			"bank_name" => "nullable|string|max:255",
			"color" => "nullable|string|max:7",
			"icon" => "nullable|string|max:50",
			"is_active" => "boolean",
			"is_default" => "boolean",
			"notes" => "nullable|string",
		];

		// For update, make fields optional
		if ($this->isMethod("PUT") || $this->isMethod("PATCH")) {
			$rules = array_map(function ($rule) {
				if (is_string($rule)) {
					return str_replace("required", "sometimes", $rule);
				}

				if (is_array($rule)) {
					return array_map(function ($r) {
						return str_replace("required", "sometimes", $r);
					}, $rule);
				}

				return $rule;
			}, $rules);
		}

		return $rules;
	}

	public function messages(): array
	{
		return [
			"name.required" => "Nama akun harus diisi",
			"type.required" => "Tipe akun harus dipilih",
			"type.enum" => "Tipe akun tidak valid",
		];
	}
}
