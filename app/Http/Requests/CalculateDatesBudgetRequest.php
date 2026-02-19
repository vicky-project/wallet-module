<?php

namespace Modules\Wallet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Wallet\Enums\PeriodType;

class CalculateDatesBudgetRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			"period_type" => ["required", Rule::enum(PeriodType::class)],
			"period_value" => "required|integer",
			"year" => "required|integer",
		];
	}

	public function messages(): array
	{
		return [
			"period_type.required" => "Period Type ia required",
			"period_value.required" => "Period value is required",
			"year.required" => "Year is required",
		];
	}
}
