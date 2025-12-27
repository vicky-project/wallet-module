<?php

namespace Modules\Wallet\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Wallet\Models\Category;
use Modules\Wallet\Repositories\CategoryRepository;
use Modules\Wallet\Http\Requests\CategoryRequest;

class CategoryController extends Controller
{
	protected $categoryRepository;

	public function __construct(CategoryRepository $categoryRepository)
	{
		$this->categoryRepository = $categoryRepository;
	}

	/**
	 * Display a listing of categories
	 */
	public function index(Request $request)
	{
		try {
			$categories = $this->categoryRepository->getUserCategories(
				$request->type,
				$request->include_inactive ?? false
			);

			return view("wallet::categories.index", compact("categories"));
		} catch (\Exception $e) {
			return response()->json(
				[
					"success" => false,
					"message" => $e->getMessage(),
					"trace" => $e->getTraceAsString(),
				],
				500
			);
		}
	}

	/**
	 * Get categories by type
	 */
	public function byType($type)
	{
		try {
			$categories = $this->categoryRepository->getUserCategories($type);

			return response()->json([
				"success" => true,
				"data" => $categories,
			]);
		} catch (\Exception $e) {
			return response()->json(
				[
					"success" => false,
					"message" => $e->getMessage(),
				],
				500
			);
		}
	}

	/**
	 * Store a newly created category
	 */
	public function store(CategoryRequest $request)
	{
		try {
			$category = $this->categoryRepository->createCategory(
				$request->validated()
			);

			return response()->json(
				[
					"success" => true,
					"message" => "Category created successfully",
					"data" => $category->load("parent", "children"),
				],
				201
			);
		} catch (\Exception $e) {
			return response()->json(
				[
					"success" => false,
					"message" => $e->getMessage(),
				],
				500
			);
		}
	}

	/**
	 * Display the specified category
	 */
	public function show(Category $category)
	{
		$this->authorize("view", $category);

		try {
			$category->load([
				"parent",
				"children" => function ($query) {
					$query->orderBy("order")->active();
				},
			]);

			return response()->json([
				"success" => true,
				"data" => $category,
			]);
		} catch (\Exception $e) {
			return response()->json(
				[
					"success" => false,
					"message" => $e->getMessage(),
				],
				500
			);
		}
	}

	/**
	 * Update the specified category
	 */
	public function update(CategoryRequest $request, Category $category)
	{
		$this->authorize("update", $category);

		try {
			$category = $this->categoryRepository->updateCategory(
				$category,
				$request->validated()
			);

			return response()->json([
				"success" => true,
				"message" => "Category updated successfully",
				"data" => $category,
			]);
		} catch (\Exception $e) {
			return response()->json(
				[
					"success" => false,
					"message" => $e->getMessage(),
				],
				500
			);
		}
	}

	/**
	 * Remove the specified category
	 */
	public function destroy(Category $category)
	{
		$this->authorize("delete", $category);

		try {
			$this->categoryRepository->deleteCategory($category);

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
				500
			);
		}
	}

	/**
	 * Reorder categories
	 */
	public function reorder(Request $request)
	{
		$request->validate([
			"categories" => "required|array",
			"categories.*.id" => "required|exists:finance_categories,id",
			"categories.*.order" => "required|integer|min:0",
		]);

		try {
			$this->categoryRepository->reorderCategories($request->categories);

			return response()->json([
				"success" => true,
				"message" => "Categories reordered successfully",
			]);
		} catch (\Exception $e) {
			return response()->json(
				[
					"success" => false,
					"message" => $e->getMessage(),
				],
				500
			);
		}
	}

	/**
	 * Get category usage statistics
	 */
	public function usage(Request $request, Category $category = null)
	{
		try {
			if ($category) {
				$this->authorize("view", $category);
				$stats = $this->categoryRepository->getCategoryUsage($category);
			} else {
				$stats = $this->categoryRepository->getAllCategoriesUsage(
					$request->start_date,
					$request->end_date
				);
			}

			return response()->json([
				"success" => true,
				"data" => $stats,
			]);
		} catch (\Exception $e) {
			return response()->json(
				[
					"success" => false,
					"message" => $e->getMessage(),
				],
				500
			);
		}
	}
}
