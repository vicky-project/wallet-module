<?php

namespace Modules\Wallet\Repositories;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Wallet\Models\Category;
use Modules\Wallet\Enums\CategoryType;
use Modules\Wallet\Enums\TransactionType;
use Illuminate\Database\Eloquent\Builder;

class CategoryRepository extends BaseRepository
{
	public function __construct(Category $model)
	{
		parent::__construct($model);
	}

	/**
	 * Get all categories for current user with filters
	 */
	public function getUserCategories(
		string $type = null,
		bool $includeInactive = false
	): Collection {
		$query = $this->model->where("user_id", auth()->id());

		if ($type) {
			$query->where("type", $type);
		}

		if (!$includeInactive) {
			$query->where("is_active", true);
		}

		return $query->orderBy("name")->get();
	}

	/**
	 * Create new category with budget limit
	 */
	public function createCategory(array $data, User $user): Category
	{
		// Convert budget limit to Money
		if (isset($data["budget_limit"])) {
			$data["budget_limit"] = $this->toDatabaseAmount(
				$this->toMoney($data["budget_limit"])
			);
		}

		// Set user_id and default icon if not provided
		$data["user_id"] = $user->id;

		// If icon is not provided, try to get from default icons based on name
		if (!isset($data["icon"]) && isset($data["name"])) {
			$data["icon"] = $this->getDefaultIcon(
				$data["name"],
				$data["type"] ?? CategoryType::EXPENSE
			);
		}

		return $this->create($data);
	}

	/**
	 * Update category with budget limit
	 */
	public function updateCategory(Category $category, array $data): Category
	{
		if (isset($data["budget_limit"])) {
			$data["budget_limit"] = $this->toDatabaseAmount(
				$this->toMoney($data["budget_limit"])
			);
		}

		// If name is changed and icon is default, update icon too
		if (isset($data["name"]) && !isset($data["icon"])) {
			$currentIcon = $category->icon;
			$defaultIcons = Category::DEFAULT_ICONS[$category->type] ?? [];
			$isDefaultIcon = in_array($currentIcon, array_values($defaultIcons));

			if ($isDefaultIcon) {
				$data["icon"] = $this->getDefaultIcon($data["name"], $category->type);
			}
		}

		$this->update($category->id, $data);
		return $this->find($category->id);
	}

	/**
	 * Delete category with validation
	 */
	public function deleteCategory(Category $category): bool
	{
		// Check if category has transactions
		if ($category->transactions()->exists()) {
			throw new \Exception("Cannot delete category that has transactions");
		}

		// Check if category has budgets
		if ($category->budgets()->exists()) {
			throw new \Exception("Cannot delete category that has budgets");
		}

		return $category->delete();
	}

	/**
	 * Get categories by type
	 */
	public function getByType(string $type, User $user): Collection
	{
		return $this->model
			->where("user_id", $user->id)
			->where("type", $type)
			->where("is_active", true)
			->orderBy("name")
			->get();
	}

	/**
	 * Get categories with monthly totals
	 */
	public function getWithMonthlyTotals(
		User $user,
		int $month = null,
		int $year = null
	): Collection {
		$month = $month ?? date("m");
		$year = $year ?? date("Y");

		return $this->model
			->where("user_id", $user->id)
			->where("type", CategoryType::EXPENSE)
			->with([
				"transactions" => function ($query) use ($month, $year) {
					$query
						->whereMonth("transaction_date", $month)
						->whereYear("transaction_date", $year)
						->where("type", TransactionType::EXPENSE);
				},
			])
			->get()
			->map(function ($category) {
				$monthlyTotal = $category->getMonthlyTotal();
				$category->monthly_total = $monthlyTotal;
				$category->budget_usage = $category->budget_limit
					? ($monthlyTotal / $category->budget_limit) * 100
					: 0;
				$category->has_budget_exceeded = $category->getHasBudgetExceededAttribute();
				return $category;
			});
	}

	/**
	 * Get categories for dropdown
	 */
	public function getForDropdown(User $user, string $type = null): array
	{
		$query = $this->model
			->where("user_id", $user->id)
			->where("is_active", true);

		if ($type) {
			$query->where("type", $type);
		}

		return $query
			->orderBy("type")
			->orderBy("name")
			->get()
			->mapWithKeys(function ($category) {
				$typeLabel =
					$category->type === CategoryType::INCOME
						? "Pemasukan"
						: "Pengeluaran";
				return [
					$category->id => "[{$typeLabel}] {$category->name}",
				];
			})
			->toArray();
	}

	/**
	 * Reorder categories
	 */
	public function reorderCategories(array $categories): void
	{
		foreach ($categories as $item) {
			$this->model
				->where("id", $item["id"])
				->update(["order" => $item["order"]]);
		}
	}

	/**
	 * Get category usage statistics
	 */
	public function getCategoryUsage(
		Category $category,
		?string $startDate = null,
		?string $endDate = null
	): array {
		$query = $category->transactions();

		if ($startDate) {
			$query->whereDate("transaction_date", ">=", $startDate);
		}

		if ($endDate) {
			$query->whereDate("transaction_date", "<=", $endDate);
		}

		$total = $query->sum("amount");
		$count = $query->count();

		$budgetLimit = $category->budget_limit ?? 0;
		$budgetUsage = $budgetLimit > 0 ? ($total / $budgetLimit) * 100 : null;

		return [
			"category" => $category,
			"total_amount" => $total,
			"transaction_count" => $count,
			"budget_usage" => $budgetUsage,
			"budget_exceeded" => $budgetLimit > 0 && $total > $budgetLimit,
			"average_transaction" => $count > 0 ? $total / $count : 0,
			"monthly_total" => $category->getMonthlyTotal(),
			"budget_usage_percentage" => $category->budget_usage_percentage,
			"formatted_budget_limit" => $category->formatted_budget_limit,
		];
	}

	/**
	 * Get all categories usage statistics
	 */
	public function getAllCategoriesUsage(
		?string $startDate = null,
		?string $endDate = null
	): Collection {
		return $this->model
			->with([
				"transactions" => function ($query) use ($startDate, $endDate) {
					if ($startDate) {
						$query->whereDate("transaction_date", ">=", $startDate);
					}
					if ($endDate) {
						$query->whereDate("transaction_date", "<=", $endDate);
					}
				},
			])
			->get()
			->map(function ($category) {
				$total = $category->transactions->sum("amount");
				$budgetLimit = $category->budget_limit ?? 0;

				return [
					"category" => $category,
					"total_amount" => $total,
					"transaction_count" => $category->transactions->count(),
					"budget_usage" =>
						$budgetLimit > 0 ? ($total / $budgetLimit) * 100 : null,
					"budget_exceeded" => $budgetLimit > 0 && $total > $budgetLimit,
					"budget_usage_percentage" => $category->budget_usage_percentage,
					"formatted_budget_limit" => $category->formatted_budget_limit,
				];
			});
	}

	/**
	 * Toggle category active status
	 */
	public function toggleStatus(Category $category): Category
	{
		$category->is_active = !$category->is_active;
		$category->save();
		return $category;
	}

	/**
	 * Get category statistics for dashboard
	 */
	public function getCategoryStats(User $user): array
	{
		$totalCategories = $this->model->where("user_id", $user->id)->count();
		$incomeCategories = $this->model
			->where("user_id", $user->id)
			->where("type", CategoryType::INCOME)
			->where("is_active", true)
			->count();
		$expenseCategories = $this->model
			->where("user_id", $user->id)
			->where("type", CategoryType::EXPENSE)
			->where("is_active", true)
			->count();

		// Get categories with budget exceeded
		$categoriesWithBudget = $this->model
			->where("user_id", $user->id)
			->where("type", CategoryType::EXPENSE)
			->where("is_active", true)
			->whereNotNull("budget_limit")
			->get();

		$budgetExceededCount = $categoriesWithBudget
			->filter(function ($category) {
				return $category->has_budget_exceeded;
			})
			->count();

		return [
			"total" => $totalCategories,
			"income" => $incomeCategories,
			"expense" => $expenseCategories,
			"active" => $this->model
				->where("user_id", $user->id)
				->where("is_active", true)
				->count(),
			"with_budget" => $categoriesWithBudget->count(),
			"budget_exceeded" => $budgetExceededCount,
		];
	}

	/**
	 * Get default icon based on category name and type
	 */
	private function getDefaultIcon(string $name, string $type): string
	{
		$lowerName = strtolower($name);
		$defaultIcons = Category::DEFAULT_ICONS[$type] ?? [];

		foreach ($defaultIcons as $key => $icon) {
			if (str_contains($lowerName, $key)) {
				return $icon;
			}
		}

		return $type === CategoryType::INCOME ? "bi-cash-stack" : "bi-wallet2";
	}

	/**
	 * Get categories with budget warnings
	 */
	public function getBudgetWarnings(User $user, int $threshold = 80): Collection
	{
		return $this->model
			->where("user_id", $user->id)
			->where("type", CategoryType::EXPENSE)
			->where("is_active", true)
			->whereNotNull("budget_limit")
			->get()
			->filter(function ($category) use ($threshold) {
				$usage = $category->budget_usage_percentage;
				return $usage >= $threshold;
			})
			->map(function ($category) {
				return [
					"category" => $category,
					"usage_percentage" => $category->budget_usage_percentage,
					"monthly_total" => $category->getMonthlyTotal(),
					"budget_limit" => $category->budget_limit,
					"formatted_budget_limit" => $category->formatted_budget_limit,
					"is_exceeded" => $category->has_budget_exceeded,
				];
			});
	}

	/**
	 * Search categories by name
	 */
	public function search(
		string $search,
		User $user,
		string $type = null
	): Collection {
		$query = $this->model
			->where("user_id", $user->id)
			->where("is_active", true);

		if ($type) {
			$query->where("type", $type);
		}

		return $query
			->where(function ($q) use ($search) {
				$q->where("name", "LIKE", "%{$search}%")->orWhere(
					"description",
					"LIKE",
					"%{$search}%"
				);
			})
			->orderBy("name")
			->get();
	}
}
