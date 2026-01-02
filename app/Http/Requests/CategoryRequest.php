<?php

namespace Modules\Wallet\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Wallet\Enums\CategoryType;

class CategoryRequest extends FormRequest
{
	public function authorize()
	{
		return true;
	}

	public function rules()
	{
		return [
			"name" => "required|string|max:100",
			"type" => ["required", Rule::enum(CategoryType::class)],
			"icon" => "nullable|string|max:50",
			"budget_limit" => "nullable|min:0",
			"is_active" => "boolean",
		];
	}

	public function attributes()
	{
		return [
			"name" => "Category Name",
			"type" => "Category Type",
			"budget_limit" => "Budget Limit",
		];
	}
}
