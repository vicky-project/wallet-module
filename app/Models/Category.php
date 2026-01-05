<?php

namespace Modules\Wallet\Models;

use Modules\Wallet\Casts\MoneyCast;
use Modules\Wallet\Enums\CategoryType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
	use HasFactory, SoftDeletes;

	protected $fillable = [
		"user_id",
		"name",
		"type",
		"icon",
		"budget_limit",
		"is_active",
	];

	protected $casts = [
		"type" => CategoryType::class,
		"budget_limit" => MoneyCast::class,
		"is_active" => "boolean",
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
	 * Get monthly transactions total
	 */
	public function getMonthlyTotal($month = null, $year = null)
	{
		$month = $month ?? date("m");
		$year = $year ?? date("Y");

		return $this->transactions()
			->whereMonth("transaction_date", $month)
			->whereYear("transaction_date", $year)
			->sum("amount");
	}

	/**
	 * Get current month's budget
	 */
	public function getCurrentBudget()
	{
		$currentMonth = date("m");
		$currentYear = date("Y");

		return $this->budgets()
			->where("month", $currentMonth)
			->where("year", $currentYear)
			->first();
	}

	/**
	 * Scope for active categories
	 */
	public function scopeActive($query)
	{
		return $query->where("is_active", true);
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

	/**
	 * Get formatted budget limit
	 */
	public function getFormattedBudgetLimitAttribute()
	{
		if (!$this->budget_limit) {
			return null;
		}

		return "Rp " .
			number_format($this->budget_limit->getAmount()->toInt(), 0, ",", ".");
	}

	/**
	 * Check if category has budget exceeded
	 */
	public function getHasBudgetExceededAttribute()
	{
		if (!$this->budget_limit) {
			return false;
		}

		$monthlyTotal = $this->getMonthlyTotal();
		return $monthlyTotal > $this->budget_limit->getAmount()->toInt();
	}

	/**
	 * Get budget usage percentage
	 */
	public function getBudgetUsagePercentageAttribute()
	{
		if (
			!$this->budget_limit ||
			$this->budget_limit->getAmount()->toInt() == 0
		) {
			return 0;
		}

		$monthlyTotal = $this->getMonthlyTotal();
		$percentage =
			($monthlyTotal / $this->budget_limit->getAmount()->toInt()) * 100;

		return min(100, round($percentage, 2));
	}
}
