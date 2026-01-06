<?php

namespace Modules\Wallet\Models;

use Modules\Wallet\Casts\MoneyCast;
use Modules\Wallet\Enums\CategoryType;
use Modules\Wallet\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
	use HasFactory, SoftDeletes;

	protected $with = ["transactions"];

	protected $fillable = [
		"user_id",
		"name",
		"type",
		"icon",
		"description",
		"is_budgetable",
	];

	protected $casts = [
		"type" => CategoryType::class,
		"is_budgetable" => "boolean",
		"created_at" => "datetime",
		"updated_at" => "datetime",
		"deleted_at" => "datetime",
	];

	// Default icons for categories
	const DEFAULT_ICONS = [
		"income" => [
			"gaji" => "bi-cash-stack",
			"investasi" => "bi-graph-up",
			"freelance" => "bi-laptop",
			"hibah" => "bi-gift",
			"lainnya" => "bi-wallet",
		],
		"expense" => [
			"makanan" => "bi-egg-fried",
			"transportasi" => "bi-car-front",
			"hiburan" => "bi-film",
			"belanja" => "bi-cart",
			"kesehatan" => "bi-heart-pulse",
			"pendidikan" => "bi-book",
			"utilitas" => "bi-lightning-charge",
			"lainnya" => "bi-wallet2",
		],
	];

	/**
	 * Relationship with User
	 */
	public function user()
	{
		return $this->belongsTo(config("auth.providers.users.model"));
	}

	/**
	 * Relationship with Transactions
	 */
	public function transactions()
	{
		return $this->hasMany(Transaction::class);
	}

	/**
	 * Relationship with Budgets
	 */
	public function budgets()
	{
		return $this->hasMany(Budget::class);
	}

	/**
	 * budget expense category
	 */
	public function scopeBudgetable($query)
	{
		return $query->where("type", CategoryType::EXPENSE);
	}

	/**
	 * Get transactions expense total
	 */
	public function getExpenseTotal($month = null, $year = null)
	{
		$month = $month ?? date("m");
		$year = $year ?? date("Y");

		return $this->transactions()
			->where("type", TransactionType::EXPENSE)
			->whereMonth("transaction_date", $month)
			->whereYear("transaction_date", $year)
			->sum("amount");
	}

	/**
	 * Scope for active categories
	 */
	public function hasBudget($month = null, $year = null)
	{
		$month = $month ?? date("m");
		$year = $year ?? date("Y");

		return $this->budgets()
			->where("month", $month)
			->where("year", $year)
			->exists();
	}

	/**
	 * Scope for income categories
	 */
	public function scopeIncome($query)
	{
		return $query->where("type", CategoryType::INCOME);
	}

	/**
	 * Scope for expense categories
	 */
	public function scopeExpense($query)
	{
		return $query->where("type", CategoryType::EXPENSE);
	}

	/**
	 * Get icon class based on category name
	 */
	public function getIconClassAttribute()
	{
		if ($this->icon) {
			return $this->icon;
		}

		$lowerName = strtolower($this->name);
		$typeIcons = self::DEFAULT_ICONS[$this->type] ?? [];

		foreach ($typeIcons as $key => $icon) {
			if (str_contains($lowerName, $key)) {
				return $icon;
			}
		}

		return $this->type === CategoryType::INCOME
			? "bi-cash-stack"
			: "bi-wallet2";
	}
}
