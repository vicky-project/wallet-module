<?php

namespace Modules\Wallet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TagRequest extends FormRequest
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
		$user = $this->user();
		$tagId = $this->route("tag");

		return [
			"name" => [
				"required",
				"string",
				"max:50",
				Rule::unique("tags")
					->where(function ($query) use ($user) {
						return $query->where("user_id", $user->id);
					})
					->ignore($tagId),
			],
			"color" => ["nullable", "string", 'regex:/^#[0-9A-F]{6}$/i'],
			"icon" => ["nullable", "string", "max:50"],
		];
	}

	/**
	 * Get custom messages for validator errors.
	 */
	public function messages(): array
	{
		return [
			"name.required" => "Nama tag wajib diisi",
			"name.unique" => "Tag dengan nama ini sudah ada",
			"color.regex" => "Format warna tidak valid. Gunakan format hex (#RRGGBB)",
		];
	}

	/**
	 * Prepare the data for validation.
	 */
	protected function prepareForValidation(): void
	{
		if ($this->has("color") && !empty($this->color)) {
			$this->merge([
				"color" => strtoupper($this->color),
			]);
		}
	}
}
