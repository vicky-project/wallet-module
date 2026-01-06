<?php

namespace Modules\Wallet\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Wallet\Models\Budget;
use Modules\Wallet\Models\Category;
use Modules\Wallet\Enums\CategoryType;
use Modules\Wallet\Repositories\BudgetRepository;
use Modules\Wallet\Http\Requests\BudgetRequest;

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

		$health = $this->budgetRepository->getBudgetHealthStatus(
			$user,
			$month,
			$year
		);

		// Get categories for filter
		$categories = Category::where("user_id", $user->id)
			->orWhereNull("user_id")
			->where("type", "expense")
			->get();

		return view(
			"wallet::budgets.index",
			compact(
				"budgets",
				"summary",
				"categories",
				"month",
				"year",
				"categoryId",
				"health"
			)
		);
	}

	/**
	 * Show the form for creating a new budget.
	 */
	public function create()
	{
		$user = auth()->user();

		// Get expense categories
		$categories = Category::expense()
			->forUser($user->id)
			->get();

		$currentMonth = Carbon::now()->month;
		$currentYear = Carbon::now()->year;

		$suggestions = $this->budgetRepository->getBudgetSuggestions(
			$user,
			$currentMonth,
			$currentYear
		);

		return view(
			"wallet::budgets.create",
			compact("categories", "currentMonth", "currentYear", "suggestions")
		);
	}

	/**
	 * Store a newly created budget.
	 */
	public function store(BudgetRequest $request)
	{
		$user = auth()->user();
		$data = $request->validated();

		$category = Category::find($data["category_id"]);
		if (!$category || $category->type !== CategoryType::EXPENSE) {
			return back()
				->withInput()
				->withErrors("Budget only can used by expense category.");
		}

		// Check if budget already exists for this category and period
		$exists = $this->budgetRepository->existsForCategory(
			$user,
			$data["category_id"],
			$data["month"],
			$data["year"]
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
			$budget = $this->budgetRepository->createBudget($data, $user);

			return redirect()
				->route("apps.budgets.index")
				->with("success", "Anggaran berhasil dibuat.");
		} catch (\Exception $e) {
			return redirect()
				->back()
				->withInput()
				->withErrors("Gagal membuat anggaran: " . $e->getMessage());
		}
	}

	/**
	 * Show the form for editing the specified budget.
	 */
	public function edit(Budget $budget)
	{
		$user = auth()->user();
		$budget->load("category");

		// Authorization check
		if ($budget->user_id != $user->id) {
			abort(403, "Unauthorized action.");
		}

		// Get expense categories
		$categories = Category::where("user_id", $user->id)
			->orWhereNull("user_id")
			->where("type", CategoryType::EXPENSE)
			->get();

		return view("wallet::budgets.edit", compact("budget", "categories"));
	}

	/**
	 * Update the specified budget.
	 */
	public function update(BudgetRequest $request, Budget $budget)
	{
		$user = auth()->user();

		// Authorization check
		if ($budget->user_id != $user->id) {
			abort(403, "Unauthorized action.");
		}

		// Check if budget already exists for this category and period (excluding current)
		$exists = Budget::where("user_id", $user->id)
			->where("category_id", $request->category_id)
			->where("month", $request->month)
			->where("year", $request->year)
			->where("id", "!=", $budget->id)
			->exists();

		if ($exists) {
			return back()
				->withInput()
				->withErrors(
					"Anggaran untuk kategori ini pada periode tersebut sudah ada."
				);
		}

		try {
			$this->budgetRepository->updateBudget($budget->id, $request->validated());

			return redirect()
				->route("apps.budgets.index")
				->with("success", "Anggaran berhasil diperbarui.");
		} catch (\Exception $e) {
			return redirect()
				->back()
				->withInput()
				->withErrors("Gagal memperbarui anggaran: " . $e->getMessage());
		}
	}

	/**
	 * Remove the specified budget.
	 */
	public function destroy(Budget $budget)
	{
		$user = auth()->user();

		// Authorization check
		if ($budget->user_id != $user->id) {
			abort(403, "Unauthorized action.");
		}

		try {
			$this->budgetRepository->delete($budget->id);

			return redirect()
				->route("apps.budgets.index")
				->with("success", "Anggaran berhasil dihapus.");
		} catch (\Exception $e) {
			return redirect()
				->back()
				->withErrors("Gagal menghapus anggaran: " . $e->getMessage());
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

			return back()->with(
				"success",
				"Jumlah terpakai anggaran berhasil diperbarui."
			);
		} catch (\Exception $e) {
			return redirect()
				->back()
				->withErrors("Gagal memperbarui jumlah terpakai: " . $e->getMessage());
		}
	}
}
