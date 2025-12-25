<?php

namespace Modules\Wallet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
			"type" => "required|in:income,expense,transfer",
			"icon" => "nullable|string|max:50",
			"order" => "nullable|integer|min:0",
			"is_active" => "boolean",
		];
	}

	public function attributes()
	{
		return [
			"name" => "Category Name",
			"type" => "Category Type",
		];
	}
}
