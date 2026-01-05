<?php

namespace Modules\Wallet\Http\Requests;

use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Wallet\Enums\CategoryType;

class BudgetRequest extends FormRequest
{
	public function authorize()
	{
		return true;
	}

	public function rules()
	{
		return [
			"category_id" => "required|exists:categories,id",
			"amount" => "required|numeric|min:1",
			"month" => "required|integer|between:1,12",
			"year" => "required|integer|min:2020|max:" . (Carbon::now()->year + 5),
		];
	}

	public function attributes()
	{
		return [
			"category_id" => "Category Name",
		];
	}
}
