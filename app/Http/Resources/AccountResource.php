<?php

namespace Modules\Wallet\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
	public function toArray($request): array
	{
		return [
			"id" => $this->id,
			"name" => $this->name,
			"type" => [
				"value" => $this->type->value,
				"label" => $this->type->label(),
			],
			"balance" => $this->formatted_balance,
			"initial_balance" => $this->formatted_initial_balance,
			"currency" => $this->currency,
			"account_number" => $this->account_number,
			"bank_name" => $this->bank_name,
			"color" => $this->color,
			"icon" => $this->icon,
			"is_active" => $this->is_active,
			"is_default" => $this->is_default,
			"notes" => $this->notes,
			"is_liability" => $this->isLiability(),
			"is_asset" => $this->isAsset(),
			"created_at" => $this->created_at->format("Y-m-d H:i:s"),
			"updated_at" => $this->updated_at->format("Y-m-d H:i:s"),
			"links" => [
				"self" => route("api.accounts.show", $this->id),
				"transactions" => route("api.transactions.index", [
					"account_id" => $this->id,
				]),
			],
		];
	}
}
