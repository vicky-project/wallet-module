<?php

namespace Modules\Wallet\Services;

use App\Models\User;
use Carbon\Carbon;
use Modules\Wallet\Models\Category;
use Modules\Wallet\Repositories\CategoryRepository;
use Modules\Wallet\Enums\CategoryType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CategoryService
{
	/**
	 * @var CategoryRepository
	 */
	protected $categoryRepository;

	/**
	 * @param CategoryRepository $categoryRepository
	 */
	public function __construct(CategoryRepository $categoryRepository)
	{
		$this->categoryRepository = $categoryRepository;
	}

	public function getDashboardData(User $user, Carbon $now): array
	{
		$startOfMonth = $now->copy()->startOfMonth();
		$endOfMonth = $now->copy()->endOfMonth();

		$data = $this->categoryRepository->getDashboardData($user, [
			"start_date" => $startOfMonth,
			"end_date" => $endOfMonth,
		]);

		return [
			"analysis" => $data["analysis"] ?? [],
			"stats" => $data["stats"] ?? [],
		];
	}

	/**
	 * Get all categories with stats for index page
	 */
	public function getIndexData(): array
	{
		try {
			return $this->categoryRepository->getUserCategoriesWithStats();
		} catch (\Exception $e) {
			throw new \RuntimeException(
				"Failed to fetch categories data: " . $e->getMessage()
			);
		}
	}

	/**
	 * Get paginated categories with filters
	 */
	public function getPaginatedCategories(
		int $perPage = 15,
		string $type = null,
		string $search = null,
		bool $includeInactive = false
	) {
		$user = auth()->user();

		// Get base query
		$query = Category::where("user_id", $user->id);

		// Apply filters
		if (
			$type &&
			in_array($type, [CategoryType::INCOME, CategoryType::EXPENSE])
		) {
			$query->where("type", $type);
		}

		if ($search) {
			$query->where(function ($q) use ($search) {
				$q->where("name", "LIKE", "%{$search}%")->orWhere(
					"description",
					"LIKE",
					"%{$search}%"
				);
			});
		}

		if (!$includeInactive) {
			$query->where("is_active", true);
		}

		// Get paginated results with budget info for current month
		return $query
			->with([
				"budgets" => function ($query) {
					$query
						->where("start_date", "<=", now())
						->where("end_date", ">=", now())
						->where("year", date("Y"));
				},
			])
			->orderBy("type")
			->orderBy("name")
			->paginate($perPage)
			->through(function ($category) {
				// Add monthly total and budget usage for expense and income categories

				switch ($category->type) {
					case CategoryType::EXPENSE:
						$activeBudget = $category->getCurrentBudget();
						$monthlyTotal = $activeBudget
							? $activeBudget->spent->getMinorAmount()->toInt()
							: 0;
						if ($activeBudget) {
							$category->budget_usage_percentage =
								$activeBudget->amount->getAmount()->toInt() > 0
									? ($monthlyTotal /
											$activeBudget->amount->getMinorAmount()->toInt()) *
										100
									: 0;
							$category->has_budget_exceeded =
								$monthlyTotal >
								$activeBudget->amount->getMinorAmount()->toInt();
							$category->budget_limit = $activeBudget->amount;
						} else {
							$category->budget_usage_percentage = 0;
							$category->has_budget_exceeded = false;
							$category->budget_limit = 0;
						}
						break;
					case CategoryType::INCOME:
						$monthlyTotal = $category->getIncomeTotal();
						break;
					default:
						$monthlyTotal = 0;
						break;
				}

				$category->monthly_total = $monthlyTotal;

				return $category;
			});
	}

	/**
	 * Create a new category
	 */
	public function createCategory(array $data): Category
	{
		$user = auth()->user();

		// Validate data
		$this->validateCategoryData($data, $user);

		try {
			DB::beginTransaction();

			// Generate slug if not provided
			if (!isset($data["slug"]) && isset($data["name"])) {
				$data["slug"] = $this->generateSlug($data["name"], $user->id);
			}

			// Create category
			$category = $this->categoryRepository->createCategory($data, $user);

			DB::commit();

			// Log activity
			activity()
				->performedOn($category)
				->causedBy($user)
				->withProperties(["attributes" => $data])
				->log("created category");

			return $category;
		} catch (\Exception $e) {
			DB::rollBack();
			throw new \RuntimeException(
				"Failed to create category: " . $e->getMessage()
			);
		}
	}

	/**
	 * Update an existing category
	 */
	public function updateCategory(Category $category, array $data): Category
	{
		$user = auth()->user();

		// Check authorization
		if ($category->user_id !== $user->id) {
			throw new \Illuminate\Auth\Access\AuthorizationException(
				"You are not authorized to update this category"
			);
		}

		try {
			DB::beginTransaction();

			// Update slug if name changed
			if (isset($data["name"]) && $data["name"] !== $category->name) {
				$data["slug"] = $this->generateSlug(
					$data["name"],
					$user->id,
					$category->id
				);
			}

			// Keep original data for activity log
			$originalData = $category->toArray();

			// Update category
			$updatedCategory = $this->categoryRepository->updateCategory(
				$category,
				$data
			);

			DB::commit();

			// Log activity
			activity()
				->performedOn($updatedCategory)
				->causedBy($user)
				->withProperties([
					"old" => $originalData,
					"new" => $updatedCategory->toArray(),
				])
				->log("updated category");

			return $updatedCategory;
		} catch (\Exception $e) {
			DB::rollBack();
			throw new \RuntimeException(
				"Failed to update category: " . $e->getMessage()
			);
		}
	}

	/**
	 * Delete a category
	 */
	public function deleteCategory(Category $category): bool
	{
		$user = auth()->user();

		// Check authorization
		if ($category->user_id !== $user->id) {
			throw new \Illuminate\Auth\Access\AuthorizationException(
				"You are not authorized to delete this category"
			);
		}

		try {
			DB::beginTransaction();

			// Keep data for activity log before deletion
			$categoryData = $category->toArray();

			// Delete category
			$result = $this->categoryRepository->deleteCategory($category);

			DB::commit();

			// Log activity
			activity()
				->causedBy($user)
				->withProperties(["category" => $categoryData])
				->log("deleted category");

			return $result;
		} catch (\Exception $e) {
			DB::rollBack();
			throw new \RuntimeException(
				"Failed to delete category: " . $e->getMessage()
			);
		}
	}

	/**
	 * Toggle category active status
	 */
	public function toggleStatus(Category $category): Category
	{
		$user = auth()->user();

		// Check authorization
		if ($category->user_id !== $user->id) {
			throw new \Illuminate\Auth\Access\AuthorizationException(
				"You are not authorized to modify this category"
			);
		}

		try {
			$oldStatus = $category->is_active;
			$category = $this->categoryRepository->toggleStatus($category);

			// Log activity
			activity()
				->performedOn($category)
				->causedBy($user)
				->withProperties([
					"old_status" => $oldStatus,
					"new_status" => $category->is_active,
				])
				->log("toggled category status");

			return $category;
		} catch (\Exception $e) {
			throw new \RuntimeException(
				"Failed to toggle category status: " . $e->getMessage()
			);
		}
	}

	/**
	 * Get category statistics for dashboard
	 */
	public function getCategoryStats(): array
	{
		$user = auth()->user();
		return $this->categoryRepository->getCategoryStats($user);
	}

	/**
	 * Get categories for dropdown
	 */
	public function getCategoriesForDropdown(string $type = null): array
	{
		$user = auth()->user();
		return $this->categoryRepository->getForDropdown($user, $type);
	}

	/**
	 * Get budget warnings
	 */
	public function getBudgetWarnings(int $threshold = 80): Collection
	{
		$user = auth()->user();
		$warnings = $this->categoryRepository->getBudgetWarnings($user, $threshold);
		return $warnings->map(function ($warning) {
			return [
				"category_id" => $warning["category"]->id ?? null,
				"category_name" => $warning["category"]->name ?? "Unknown",
				"budget_name" => $warning["budget"]->name ?? "Unknown",
				"usage_percentage" => $warning["usage_percentage"] ?? 0,
				"spent" => $warning["total_spent"] ?? 0,
				"budget_amount" => $warning["budget"]->amount ?? 0,
				"message" =>
					"{$warning["category"]->name} telah menggunakan " .
					round($warning["usage_percentage"]) .
					"% dari budget",
			];
		});
	}

	/**
	 * Get categories with monthly totals
	 */
	public function getCategoriesWithMonthlyTotals(
		int $month = null,
		int $year = null
	): Collection {
		$user = auth()->user();
		return $this->categoryRepository->getWithMonthlyTotals(
			$user,
			$month,
			$year
		);
	}

	/**
	 * Generate unique slug
	 */
	private function generateSlug(
		string $name,
		int $userId,
		?int $exceptId = null
	): string {
		$slug = \Illuminate\Support\Str::slug($name);
		$originalSlug = $slug;
		$counter = 1;

		while (true) {
			$query = Category::where("user_id", $userId)->where("slug", $slug);

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

	/**
	 * Get category usage statistics
	 */
	public function getCategoryUsage(
		Category $category,
		?string $startDate = null,
		?string $endDate = null
	): array {
		$user = auth()->user();

		// Check authorization
		if ($category->user_id !== $user->id) {
			throw new \Illuminate\Auth\Access\AuthorizationException(
				"You are not authorized to view this category"
			);
		}

		return $this->categoryRepository->getCategoryUsage(
			$category,
			$startDate,
			$endDate
		);
	}

	/**
	 * Get all categories usage statistics
	 */
	public function getAllCategoriesUsage(
		?string $startDate = null,
		?string $endDate = null
	): Collection {
		$user = auth()->user();
		return $this->categoryRepository->getAllCategoriesUsage(
			$user,
			$startDate,
			$endDate
		);
	}

	/**
	 * Search categories
	 */
	public function searchCategories(
		string $search,
		string $type = null
	): Collection {
		$user = auth()->user();
		return $this->categoryRepository->search($search, $user, $type);
	}

	/**
	 * Get popular categories
	 */
	public function getPopularCategories(
		int $limit = 5,
		string $type = null
	): Collection {
		$user = auth()->user();
		return $this->categoryRepository->getPopularCategories(
			$user,
			$limit,
			$type
		);
	}

	/**
	 * Get unbudgeted categories
	 */
	public function getUnbudgetedCategories(
		int $month = null,
		int $year = null
	): Collection {
		$user = auth()->user();
		return $this->categoryRepository->getUnbudgetedCategories(
			$user,
			$month,
			$year
		);
	}

	/**
	 * Bulk update categories
	 */
	public function bulkUpdate(array $categoryIds, array $data): int
	{
		$user = auth()->user();

		// Validate data for bulk update
		$validatedData = validator($data, [
			"is_active" => ["boolean"],
			"is_budgetable" => ["boolean"],
		])->validate();

		try {
			DB::beginTransaction();

			$count = Category::where("user_id", $user->id)
				->whereIn("id", $categoryIds)
				->update($validatedData);

			DB::commit();

			// Invalidate cache
			$this->categoryRepository->invalidateUserCategoryCache($user->id);

			// Log activity
			activity()
				->causedBy($user)
				->withProperties([
					"category_ids" => $categoryIds,
					"updates" => $validatedData,
				])
				->log("bulk updated categories");

			return $count;
		} catch (\Exception $e) {
			DB::rollBack();
			throw new \RuntimeException(
				"Failed to bulk update categories: " . $e->getMessage()
			);
		}
	}

	/**
	 * Import categories from template
	 */
	public function importCategories(array $categories): array
	{
		$user = auth()->user();
		$results = [
			"success" => 0,
			"failed" => 0,
			"errors" => [],
		];

		try {
			DB::beginTransaction();

			foreach ($categories as $index => $categoryData) {
				try {
					// Add user_id to data
					$categoryData["user_id"] = $user->id;

					// Generate slug if not provided
					if (!isset($categoryData["slug"]) && isset($categoryData["name"])) {
						$categoryData["slug"] = $this->generateSlug(
							$categoryData["name"],
							$user->id
						);
					}

					// Create category
					Category::create($categoryData);
					$results["success"]++;
				} catch (\Exception $e) {
					$results["failed"]++;
					$results["errors"][] = [
						"row" => $index + 1,
						"error" => $e->getMessage(),
					];
				}
			}

			DB::commit();

			// Invalidate cache
			$this->categoryRepository->invalidateUserCategoryCache($user->id);

			// Log activity
			activity()
				->causedBy($user)
				->withProperties(["results" => $results])
				->log("imported categories");

			return $results;
		} catch (\Exception $e) {
			DB::rollBack();
			throw new \RuntimeException(
				"Failed to import categories: " . $e->getMessage()
			);
		}
	}
}
