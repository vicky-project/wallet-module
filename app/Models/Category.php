<?php

namespace Modules\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
	use SoftDeletes;

	protected $fillable = [
		"user_id",
		"name",
		"type",
		"icon",
		"is_active",
		"order",
	];

	protected $casts = [
		"is_active" => "boolean",
	];

	public function user()
	{
		return $this->belongsTo(config("auth.providers.users.model"));
	}

	public function transactions()
	{
		return $this->hasMany(Transaction::class, "category", "name");
	}

	public function scopeIncome($query)
	{
		return $query->where("type", "income");
	}

	public function scopeExpense($query)
	{
		return $query->where("type", "expense");
	}

	public function scopeTransfer($query)
	{
		return $query->where("type", "transfer");
	}

	public function scopeActive($query)
	{
		return $query->where("is_active", true);
	}

	public function scopeByUser($query, $userId = null)
	{
		if ($userId) {
			return $query->where("user_id", $userId);
		}
		return $query->whereNull("user_id");
	}
}
