<?php

namespace Modules\Wallet\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Wallet\Models\Budget;
use Modules\Wallet\Models\Category;
use Modules\Wallet\Repositories\BudgetRepository;
use Carbon\Carbon;

class BudgetController extends BaseController
{
	protected $budgetRepository;

	public function __construct(BudgetRepository $budgetRepository)
	{
		$this->budgetRepository = $budgetRepository;
	}

	/**
	 * Display a listing of budgets.
	 */
	public function index(Request $request)
	{
		$user = auth()->user();

		// Get filter parameters
		$month = $request->get("month", Carbon::now()->month);
		$year = $request->get("year", Carbon::now()->year);
		$categoryId = $request->get("category_id");

		$filters = [
			"month" => $month,
			"year" => $year,
		];

		if ($categoryId) {
			$filters["category_id"] = $categoryId;
		}

		// Get budgets
		$budgets = $this->budgetRepository->getUserBudgets($user, $filters);

		// Get summary
		$summary = $this->budgetRepository->getBudgetSummary($user, $month, $year);

		// Get categories for filter
		$categories = Category::where("user_id", $user->id)
			->orWhereNull("user_id")
			->where("type", "expense")
			->get();

		return view(
			"wallet::budgets.index",
			compact("budgets", "summary", "categories", "month", "year", "categoryId")
		);
	}

	/**
	 * Show the form for creating a new budget.
	 */
	public function create()
	{
		$user = auth()->user();

		// Get expense categories
		$categories = Category::where("user_id", $user->id)
			->orWhereNull("user_id")
			->where("type", "expense")
			->get();

		$currentMonth = Carbon::now()->month;
		$currentYear = Carbon::now()->year;

		return view(
			"wallet::budgets.create",
			compact("categories", "currentMonth", "currentYear")
		);
	}

	/**
	 * Store a newly created budget.
	 */
	public function store(Request $request)
	{
		$request->validate([
			"category_id" => "required|exists:categories,id",
			"amount" => "required|numeric|min:1",
			"month" => "required|integer|between:1,12",
			"year" => "required|integer|min:2020|max:" . (Carbon::now()->year + 5),
		]);

		$user = auth()->user();

		// Check if budget already exists for this category and period
		$exists = $this->budgetRepository->existsForCategory(
			$user,
			$request->category_id,
			$request->month,
			$request->year
		);

		if ($exists) {
			return redirect()
				->back()
				->withInput()
				->with(
					"error",
					"Anggaran untuk kategori ini pada periode tersebut sudah ada."
				);
		}

		try {
			$budget = $this->budgetRepository->createBudget($request->all(), $user);

			return redirect()
				->route("apps.budgets.index")
				->with("success", "Anggaran berhasil dibuat.");
		} catch (\Exception $e) {
			return redirect()
				->back()
				->withInput()
				->with("error", "Gagal membuat anggaran: " . $e->getMessage());
		}
	}

	/**
	 * Show the form for editing the specified budget.
	 */
	public function edit($id)
	{
		$user = auth()->user();
		$budget = Budget::with("category")->findOrFail($id);

		// Authorization check
		if ($budget->user_id != $user->id) {
			abort(403, "Unauthorized action.");
		}

		// Get expense categories
		$categories = Category::where("user_id", $user->id)
			->orWhereNull("user_id")
			->where("type", "expense")
			->get();

		return view("wallet::budgets.edit", compact("budget", "categories"));
	}

	/**
	 * Update the specified budget.
	 */
	public function update(Request $request, $id)
	{
		$request->validate([
			"category_id" => "required|exists:categories,id",
			"amount" => "required|numeric|min:1",
			"month" => "required|integer|between:1,12",
			"year" => "required|integer|min:2020|max:" . (Carbon::now()->year + 5),
		]);

		$user = auth()->user();
		$budget = Budget::findOrFail($id);

		// Authorization check
		if ($budget->user_id != $user->id) {
			abort(403, "Unauthorized action.");
		}

		// Check if budget already exists for this category and period (excluding current)
		$exists = Budget::where("user_id", $user->id)
			->where("category_id", $request->category_id)
			->where("month", $request->month)
			->where("year", $request->year)
			->where("id", "!=", $id)
			->exists();

		if ($exists) {
			return redirect()
				->back()
				->withInput()
				->with(
					"error",
					"Anggaran untuk kategori ini pada periode tersebut sudah ada."
				);
		}

		try {
			$this->budgetRepository->updateBudget($id, $request->all());

			return redirect()
				->route("apps.budgets.index")
				->with("success", "Anggaran berhasil diperbarui.");
		} catch (\Exception $e) {
			return redirect()
				->back()
				->withInput()
				->with("error", "Gagal memperbarui anggaran: " . $e->getMessage());
		}
	}

	/**
	 * Remove the specified budget.
	 */
	public function destroy($id)
	{
		$user = auth()->user();
		$budget = Budget::findOrFail($id);

		// Authorization check
		if ($budget->user_id != $user->id) {
			abort(403, "Unauthorized action.");
		}

		try {
			$this->budgetRepository->delete($id);

			return redirect()
				->route("apps.budgets.index")
				->with("success", "Anggaran berhasil dihapus.");
		} catch (\Exception $e) {
			return redirect()
				->back()
				->with("error", "Gagal menghapus anggaran: " . $e->getMessage());
		}
	}

	/**
	 * Update spent amounts for current month
	 */
	public function updateSpent()
	{
		$user = auth()->user();

		try {
			$this->budgetRepository->updateAllBudgetsSpent($user);

			return redirect()
				->back()
				->with("success", "Jumlah terpakai anggaran berhasil diperbarui.");
		} catch (\Exception $e) {
			return redirect()
				->back()
				->with(
					"error",
					"Gagal memperbarui jumlah terpakai: " . $e->getMessage()
				);
		}
	}
}
