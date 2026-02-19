<?php

namespace Modules\Wallet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Wallet\Enums\RecurringFreq;
use Modules\Wallet\Enums\TransactionType;

class RecurringTransactionRequest extends FormRequest
{
	/**
	 * Determine if the user is authorized to make this request.
	 */
	public function authorize(): bool
	{
		return true;
	}

	/**
	 * Get the validation rules that apply to the request.
	 */
	public function rules(): array
	{
		$rules = [
			"account_id" => "required|exists:accounts,id",
			"category_id" => "required|exists:categories,id",
			"type" => ["required", Rule::enum(TransactionType::class)],
			"amount" => "required|numeric|min:100",
			"description" => "required|string|max:255",
			"frequency" => ["required", Rule::enum(RecurringFreq::class)],
			"interval" => "required|integer|min:1|max:12",
			"start_date" => "required|date",
			"end_date" => "nullable|date|after:start_date",
			"day_of_week" => "nullable|integer|min:0|max:6",
			"day_of_month" => "nullable|integer|min:1|max:31",
			"is_active" => "boolean",
			"remaining_occurrences" => "nullable|integer|min:1",
		];

		// Add transfer-specific validation
		if ($this->input("type") === TransactionType::TRANSFER->value) {
			$rules["to_account_id"] =
				"required|exists:accounts,id|different:account_id";
		}

		// Add frequency-specific validation
		switch ($this->input("frequency")) {
			case RecurringFreq::WEEKLY:
				$rules["day_of_week"] = "required|integer|min:0|max:6";
				break;
			case RecurringFreq::MONTHLY:
			case RecurringFreq::QUARTERLY:
				$rules["day_of_month"] = "required|integer|min:1|max:31";
				break;
		}

		return $rules;
	}

	/**
	 * Get custom messages for validator errors.
	 */
	public function messages(): array
	{
		return [
			"account_id.required" => "Akun harus dipilih.",
			"category_id.required" => "Kategori harus dipilih.",
			"amount.min" => "Jumlah minimal adalah Rp 100.",
			"to_account_id.different" =>
				"Akun tujuan tidak boleh sama dengan akun sumber.",
			"day_of_week.required" =>
				"Hari dalam minggu harus dipilih untuk frekuensi mingguan.",
			"day_of_month.required" =>
				"Hari dalam bulan harus dipilih untuk frekuensi bulanan/triwulan.",
		];
	}

	/**
	 * Prepare the data for validation.
	 */
	protected function prepareForValidation()
	{
		// Set user_id for authorization
		$this->merge([
			"user_id" => auth()->id(),
		]);

		// Set default interval if not provided
		if (!$this->has("interval")) {
			$this->merge(["interval" => 1]);
		}

		// Set default is_active if not provided
		if (!$this->has("is_active")) {
			$this->merge(["is_active" => true]);
		}
	}
}
