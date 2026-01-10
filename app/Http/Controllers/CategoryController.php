<?php

namespace Modules\Wallet\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Wallet\Models\Category;
use Modules\Wallet\Services\CategoryService;
use Modules\Wallet\Http\Requests\CategoryRequest;

class CategoryController extends Controller
{
	/**
	 * @var CategoryService
	 */
	protected $categoryService;

	/**
	 * @param CategoryService $categoryService
	 */
	public function __construct(CategoryService $categoryService)
	{
		$this->categoryService = $categoryService;
	}

	/**
	 * Display a listing of categories
	 */
	public function index(Request $request)
	{
		$data = $this->categoryService->getIndexData();
		$categories = $this->categoryService->getPaginatedCategories(
			perPage: $request->get("per_page", 15),
			type: $request->get("type"),
			search: $request->get("search"),
			includeInactive: $request->boolean("include_inactive")
		);

		// Get budget warnings for alert
		$budgetWarnings = $this->categoryService->getBudgetWarnings();

		return view("wallet::categories.index", [
			"categories" => $categories,
			"stats" => $data["stats"],
			"budgetWarnings" => $budgetWarnings,
		]);
	}

	public function create(Request $request)
	{
		return view("wallet::categories.create");
	}

	/**
	 * Store a newly created category
	 */
	public function store(CategoryRequest $request)
	{
		try {
			$category = $this->categoryService->createCategory($request->validated());

			return response()->json(
				[
					"success" => true,
					"message" => "Category created successfully",
					"category" => $category,
				],
				201
			);
		} catch (\Exception $e) {
			return response()->json(
				[
					"success" => false,
					"message" => $e->getMessage(),
				],
				400
			);
		}
	}

	public function edit(Request $request, Category $category)
	{
		return view("wallet::categories.edit", compact("category"));
	}

	/**
	 * Update the specified category
	 */
	public function update(CategoryRequest $request, Category $category)
	{
		try {
			$updatedCategory = $this->categoryService->updateCategory(
				$category,
				$request->validated()
			);

			return response()->json([
				"success" => true,
				"message" => "Category updated successfully",
				"category" => $updatedCategory,
			]);
		} catch (\Exception $e) {
			return response()->json(
				[
					"success" => false,
					"message" => $e->getMessage(),
				],
				400
			);
		}
	}

	/**
	 * Remove the specified category
	 */
	public function destroy(Category $category)
	{
		try {
			$this->categoryService->deleteCategory($category);

			return response()->json([
				"success" => true,
				"message" => "Category deleted successfully",
			]);
		} catch (\Exception $e) {
			return response()->json(
				[
					"success" => false,
					"message" => $e->getMessage(),
				],
				400
			);
		}
	}

	/**
	 * Toggle category status
	 */
	public function toggleStatus(Category $category)
	{
		try {
			$updatedCategory = $this->categoryService->toggleStatus($category);

			return response()->json([
				"success" => true,
				"message" => "Category status updated",
				"category" => $updatedCategory,
			]);
		} catch (\Exception $e) {
			return response()->json(
				[
					"success" => false,
					"message" => $e->getMessage(),
				],
				400
			);
		}
	}

	/**
	 * Get category usage statistics
	 */
	public function usage(Category $category, Request $request)
	{
		try {
			$usage = $this->categoryService->getCategoryUsage(
				$category,
				$request->get("start_date"),
				$request->get("end_date")
			);

			return response()->json([
				"success" => true,
				"data" => $usage,
			]);
		} catch (\Exception $e) {
			return response()->json(
				[
					"success" => false,
					"message" => $e->getMessage(),
				],
				400
			);
		}
	}

	/**
	 * Bulk update categories
	 */
	public function bulkUpdate(Request $request)
	{
		$request->validate([
			"category_ids" => "required|array",
			"category_ids.*" => "exists:categories,id",
			"is_active" => "boolean",
			"is_budgetable" => "boolean",
		]);

		try {
			$count = $this->categoryService->bulkUpdate(
				$request->category_ids,
				$request->only(["is_active", "is_budgetable"])
			);

			return response()->json([
				"success" => true,
				"message" => "{$count} categories updated successfully",
			]);
		} catch (\Exception $e) {
			return response()->json(
				[
					"success" => false,
					"message" => $e->getMessage(),
				],
				400
			);
		}
	}

	public function bulkDelete(Request $request)
	{
	}

	/**
	 * Get categories for dropdown
	 */
	public function dropdown(Request $request)
	{
		$categories = $this->categoryService->getCategoriesForDropdown(
			$request->get("type")
		);

		return response()->json([
			"success" => true,
			"data" => $categories,
		]);
	}
}
