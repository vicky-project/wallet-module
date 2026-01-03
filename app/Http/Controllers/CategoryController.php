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
				$request->include_inactive ?? true
			);
			dd($categories);

			$stats = $this->categoryRepository->getCategoryStats(auth()->user());

			return view("wallet::categories.index", compact("categories", "stats"));
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

	public function create()
	{
		return view("wallet::categories.create");
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
		//dd($request->validated());
		try {
			$category = $this->categoryRepository->createCategory(
				$request->validated(),
				auth()->user()
			);

			return back()->with("success", "Category created successfully");
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
		try {
			return view("wallet::categories.show", compact("category"));
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
			$category = $this->categoryRepository->updateCategory(
				$category,
				$request->validated()
			);

			return redirect()
				->route("apps.categories.index")
				->with("success", "Category updated successfully");
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
		try {
			$this->categoryRepository->deleteCategory($category);

			return back()->with("success", "Category deleted successfully");
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

	public function toggleStatus(Category $category)
	{
		$category = $this->categoryRepository->toggleStatus($category);

		return back()->with("success", "Berhasil mengubah status kategori.");
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

	// Tambahkan method ini ke controller yang sudah ada
	/**
	 * Get budget warnings for categories
	 */
	public function budgetWarnings(Request $request)
	{
		try {
			$threshold = $request->threshold ?? 80;
			$warnings = $this->categoryRepository->getBudgetWarnings(
				auth()->user(),
				$threshold
			);

			if ($request->expectsJson()) {
				return response()->json([
					"success" => true,
					"data" => $warnings,
					"count" => $warnings->count(),
				]);
			}

			return view("wallet::categories.budget-warnings", compact("warnings"));
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
