<?php

namespace Modules\Wallet\Models;

use Modules\Wallet\Casts\MoneyCast;
use Modules\Wallet\Enums\CategoryType;
use Modules\Wallet\Enums\TransactionType;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
	use HasFactory, SoftDeletes;

	//protected $with = ["transactions", "budgets"];

	protected $fillable = [
		"user_id",
		"name",
		"type",
		"icon",
		"slug",
		"description",
		"is_budgetable",
		"is_active",
	];

	protected $casts = [
		"type" => CategoryType::class,
		"is_active" => "boolean",
		"is_budgetable" => "boolean",
		"created_at" => "datetime",
		"updated_at" => "datetime",
		"deleted_at" => "datetime",
	];

	protected $attributes = ["is_budgetable" => false, "is_active" => true];

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

	public static function boot()
	{
		parent::boot();

		static::creating(function ($category) {
			if (empty($category->icon)) {
				$category->icon = self::getDefaultIcon(
					$category->name,
					$category->type
				);
			}

			$category->is_budgetable = $category->type === CategoryType::EXPENSE;
			$category->slug = self::generateSlug($category->name, $category->user_id);
		});

		static::updating(function ($category) {
			if ($category->type === CategoryType::INCOME) {
				$category->is_budgetable = false;
			}

			$category->slug = self::generateSlug(
				$category->name,
				$category->user_id,
				$category->id
			);
		});
	}

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
		return $query
			->where("type", CategoryType::EXPENSE)
			->where("is_budgetable", true);
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

	public function scopeActive($query)
	{
		return $query->where("is_active", true);
	}

	public function scopeForUser($query, $userId = null)
	{
		return $query->where(function ($q) use ($userId) {
			$q->where("user_id", $userId)->orWhereNull("user_id");
		});
	}

	/**
	 * Scope for active budgets
	 */
	public function hasCurrentBudget($month = null, $year = null): ?Budget
	{
		$currentDate = now();

		return $this->budgets()
			->active()
			->where("start_date", "<=", $currentDate)
			->where("end_date", ">=", $currentDate)
			->exists();
	}

	/**
	 * get for active budget
	 */
	public function getCurrentBudget($month = null, $year = null)
	{
		$currentDate = now();

		return $this->budgets()
			->active()
			->where("start_date", "<=", $currentDate)
			->where("end_date", ">=", $currentDate)
			->first();
	}

	public function getExpenseTotal($month = null, $year = null)
	{
		$month = $month ?? date("m");
		$year = $year ?? date("Y");

		return $this->transactions()
			->expense()
			->whereMonth("transaction_date", $month)
			->whereYear("transaction_date", $year)
			->sum("amount");
	}

	public function getIncomeTotal($month = null, $year = null)
	{
		$month = $month ?? date("m");
		$year = $year ?? date("Y");

		return $this->transactions()
			->income()
			->whereMonth("transaction_date", $month)
			->whereYear("transaction_date", $year)
			->sum("amount");
	}

	public function getFormattedExpenseTotal($month = null, $year = null)
	{
		$month = $month ?? date("m");
		$year = $year ?? date("Y");

		return "Rp " .
			number_format($this->getExpenseTotal($month, $year), 0, ",", ".");
	}

	public function getFormattedIncomeTotal($month = null, $year = null)
	{
		$month = $month ?? date("m");
		$year = $year ?? date("Y");

		return "Rp " .
			number_format($this->getIncomeTotal($month, $year), 0, ",", ".");
	}

	public function getCanDeleteAttribute()
	{
		$hasTransactions = $this->transactions()->exists();

		$hasBudgets = $this->budgets()->exists();

		return !$hasTransactions && !$hasBudgets;
	}

	public function getTypeLabelAttribute()
	{
		return $this->type === CategoryType::INCOME ? "Pendapatan" : "Pengeluaran";
	}

	public function getTypeColorAttribute()
	{
		return $this->type === CategoryType::INCOME ? "success" : "danger";
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

	public static function getDefaultIcon($name, $type)
	{
		return self::DEFAULT_ICONS[$type][$name] ?? "bi-bag";
	}

	public static function generateSlug(
		string $name,
		int $userId,
		?int $exceptId = null
	): string {
		$slug = Str::slug($name);
		$originalSlug = $slug;
		$counter = 1;

		while (true) {
			$query = self::where("user_id", $userId)->where("slug", $slug);

			if ($exceptId) {
				$query->where("id", "!=", $exceptId);
			}

			if (!$query->exists()) {
				break;
			}

			$slug = $originalSlug . "-" . $counter++;
		}

		return $slug;
	}
}
