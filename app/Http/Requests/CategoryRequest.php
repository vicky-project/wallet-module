<?php

namespace Modules\Wallet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Wallet\Enums\CategoryType;
use Modules\Wallet\Constants\Permissions;

class CategoryRequest extends FormRequest
{
	public function authorize(): bool
	{
		return auth()->check() &&
			($this->isMethod("PUT")
				? auth()
					->user()
					->can(Permissions::EDIT_CATEGORIES)
				: auth()
					->user()
					->can(Permissions::CREATE_CATEGORIES));
	}

	public function rules(): array
	{
		$categoryId = $this->route("category")
			? $this->route("category")->id
			: null;
		$userId = auth()->id();

		return [
			"name" => [
				"required",
				"string",
				"max:100",
				Rule::unique("categories")
					->where(function ($query) use ($userId) {
						return $query->where("user_id", $userId);
					})
					->ignore($categoryId),
			],
			"type" => ["required", Rule::enum(CategoryType::class)],
			"icon" => ["nullable", "string", "max:50"],
			"description" => ["nullable", "string", "max:500"],
			"is_active" => ["boolean"],
			"is_budgetable" => ["boolean"],
			"slug" => [
				"nullable",
				"string",
				"max:120",
				Rule::unique("categories")
					->where(function ($query) use ($userId) {
						return $query->where("user_id", $userId);
					})
					->ignore($categoryId),
			],
		];
	}

	public function messages(): array
	{
		return [
			"name.required" => "Nama kategori wajib diisi",
			"name.unique" => "Nama kategori sudah digunakan",
			"type.required" => "Tipe kategori wajib dipilih",
			"type.in" => "Tipe kategori tidak valid",
		];
	}
}
