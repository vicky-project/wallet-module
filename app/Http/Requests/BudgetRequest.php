<?php

namespace Modules\Wallet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Wallet\Enums\PeriodType;

class BudgetRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		$budgetId = $this->route("budget") ? $this->route("budget")->id : null;
		$userId = auth()->id();

		$rules = [
			"category_id" => [
				"required",
				Rule::exists("categories", "id")->where(function ($query) use (
					$userId
				) {
					$query->where("user_id", $userId)->where("type", "expense");
				}),
			],
			"name" => ["nullable", "string", "max:100"],
			"period_type" => ["required", Rule::enum(PeriodType::class)],
			"period_value" => ["required", "integer", "min:1"],
			"year" => ["required", "integer", "min:2000", "max:2100"],
			"start_date" => ["required", "date"],
			"end_date" => ["required", "date", "after_or_equal:start_date"],
			"amount" => ["required", "integer", "min:1000"],
			"rollover_unused" => ["boolean"],
			"rollover_limit" => ["nullable", "integer", "min:0"],
			"is_active" => ["boolean"],
			"accounts" => ["array"],
			"accounts.*" => [
				Rule::exists("accounts", "id")->where("user_id", $userId),
			],
		];

		// Unique constraint for budget period
		if ($budgetId) {
			$rules["category_id"][] = Rule::unique("budgets")
				->where(function ($query) use ($userId) {
					return $query
						->where("user_id", $userId)
						->where("period_type", $this->period_type)
						->where("period_value", $this->period_value)
						->where("year", $this->year);
				})
				->ignore($budgetId);
		} else {
			$rules["category_id"][] = Rule::unique("budgets")->where(function (
				$query
			) use ($userId) {
				return $query
					->where("user_id", $userId)
					->where("period_type", $this->period_type)
					->where("period_value", $this->period_value)
					->where("year", $this->year);
			});
		}

		return $rules;
	}

	public function messages(): array
	{
		return [
			"category_id.required" => "Kategori wajib dipilih",
			"category_id.exists" => "Kategori tidak valid",
			"category_id.unique" =>
				"Sudah ada budget untuk kategori ini pada periode yang sama",
			"period_type.required" => "Tipe periode wajib dipilih",
			"period_type.in" => "Tipe periode tidak valid",
			"amount.required" => "Jumlah budget wajib diisi",
			"amount.min" => "Jumlah budget minimal Rp 1.000",
			"start_date.required" => "Tanggal mulai wajib diisi",
			"end_date.required" => "Tanggal selesai wajib diisi",
			"end_date.after_or_equal" =>
				"Tanggal selesai harus setelah atau sama dengan tanggal mulai",
		];
	}
}
