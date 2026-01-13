<?php

namespace Modules\Wallet\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Wallet\Models\Budget;
use Modules\Wallet\Services\BudgetService;
use Modules\Wallet\Enums\TransactionType;
use Modules\Wallet\Http\Requests\BudgetRequest;
use Modules\Wallet\Http\Requests\CalculateDatesBudgetRequest;

class BudgetController extends Controller
{
	/**
	 * @var BudgetService
	 */
	protected $budgetService;

	/**
	 * @param BudgetService $budgetService
	 */
	public function __construct(BudgetService $budgetService)
	{
		$this->budgetService = $budgetService;
	}

	/**
	 * Display a listing of budgets
	 */
	public function index(Request $request)
	{
		$filters = $request->only([
			"category_id",
			"period_type",
			"year",
			"is_active",
			"search",
		]);
		$data = $this->budgetService->getIndexData($filters);

		// Get additional data for filters
		$categories = \Modules\Wallet\Models\Category::where(
			"user_id",
			auth()->id()
		)
			->expense()
			->orderBy("name")
			->get();

		$periodTypes = \Modules\Wallet\Enums\PeriodType::cases();

		return view(
			"wallet::budgets.index",
			array_merge($data, [
				"categories" => $categories,
				"periodTypes" => $periodTypes,
			])
		);
	}

	/**
	 * Show the form for creating a new budget
	 */
	public function create()
	{
		$data = $this->budgetService->getCreateData();
		return view("wallet::budgets.create", $data);
	}

	/**
	 * Store a newly created budget
	 */
	public function store(BudgetRequest $request)
	{
		$user = $request->user();
		try {
			$budget = $this->budgetService->createBudget(
				$user,
				$request->validated()
			);

			return redirect()
				->route("apps.budgets.show", $budget)
				->with("success", "Budget berhasil dibuat");
		} catch (\Exception $e) {
			return back()
				->withInput()
				->withErrors($e->getMessage());
		}
	}

	/**
	 * Display the specified budget
	 */
	public function show(Budget $budget)
	{
		// Authorization check
		if ($budget->user_id !== auth()->id()) {
			abort(403);
		}

		// Load relationships
		$budget->load(["category", "accounts", "user"]);

		// Get transactions for this budget period
		$transactions = $budget->category
			->transactions()
			->expense()
			->whereBetween("transaction_date", [
				$budget->start_date,
				$budget->end_date,
			])
			->with("account")
			->orderBy("transaction_date", "desc")
			->paginate(20);

		// Get budget statistics
		$stats = [
			"total_transactions" => $transactions->total(),
			"average_transaction" => $transactions->avg("amount") ?? 0,
			"largest_transaction" => $transactions->max("amount") ?? 0,
			"transactions_today" => $budget->category
				->transactions()
				->expense()
				->whereDate("transaction_date", today())
				->whereBetween("transaction_date", [
					$budget->start_date,
					$budget->end_date,
				])
				->count(),
		];

		return view(
			"wallet::budgets.show",
			compact("budget", "transactions", "stats")
		);
	}

	/**
	 * Show the form for editing the specified budget
	 */
	public function edit(Budget $budget)
	{
		// Authorization check
		if ($budget->user_id !== auth()->id()) {
			abort(403);
		}

		$data = $this->budgetService->getCreateData();
		$data["budget"] = $budget;
		$data["selectedAccounts"] = $budget->accounts->pluck("id")->toArray();

		return view("wallet::budgets.edit", $data);
	}

	/**
	 * Update the specified budget
	 */
	public function update(BudgetRequest $request, Budget $budget)
	{
		try {
			$budget = $this->budgetService->updateBudget(
				$budget,
				$request->validated()
			);

			return redirect()
				->route("wallet.budgets.show", $budget)
				->with("success", "Budget berhasil diperbarui");
		} catch (\Exception $e) {
			return back()
				->withInput()
				->with("error", $e->getMessage());
		}
	}

	/**
	 * Remove the specified budget
	 */
	public function destroy(Budget $budget)
	{
		try {
			$this->budgetService->deleteBudget($budget);

			return redirect()
				->route("wallet.budgets.index")
				->with("success", "Budget berhasil dihapus");
		} catch (\Exception $e) {
			return back()->with("error", $e->getMessage());
		}
	}

	/**
	 * Calculate period dates
	 */
	public function calculateDates(CalculateDatesBudgetRequest $request)
	{
		$data = $request->validated();

		$dates = $this->budgetService->calculatePeriodDates(
			$data["period_type"],
			$data["period_value"],
			$data["year"]
		);

		return response()->json([
			"success" => true,
			"dates" => [
				"start_date" => $dates["start_date"]->format("Y-m-d"),
				"end_date" => $dates["end_date"]->format("Y-m-d"),
			],
		]);
	}

	/**
	 * Get suggested amount for category
	 */
	public function suggestedAmount($categoryId)
	{
		$amount = $this->budgetService->getSuggestedAmount($categoryId);

		return response()->json([
			"suggested_amount" => $amount,
		]);
	}

	/**
	 * Toggle budget status
	 */
	public function toggleStatus(Budget $budget)
	{
		try {
			$budget = $this->budgetService->toggleStatus($budget);

			return response()->json([
				"success" => true,
				"message" => "Status budget berhasil diubah",
				"budget" => $budget,
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
	 * Create next period budget
	 */
	public function createNextPeriod(Budget $budget)
	{
		try {
			$nextBudget = $this->budgetService->createNextPeriodBudget($budget);

			return redirect()
				->route("wallet.budgets.show", $nextBudget)
				->with("success", "Budget periode berikutnya berhasil dibuat");
		} catch (\Exception $e) {
			return back()->with("error", $e->getMessage());
		}
	}

	/**
	 * Update all spent amounts
	 */
	public function updateSpentAmounts()
	{
		try {
			$this->budgetService->updateAllSpentAmounts();

			return back()->with(
				"success",
				"Jumlah terpakai semua budget berhasil diperbarui"
			);
		} catch (\Exception $e) {
			return back()->with("error", $e->getMessage());
		}
	}

	/**
	 * Bulk update budgets
	 */
	public function bulkUpdate(Request $request)
	{
		$request->validate([
			"budget_ids" => "required|array",
			"budget_ids.*" => "exists:budgets,id,user_id," . auth()->id(),
			"action" => "required|in:activate,deactivate,delete",
		]);

		try {
			$count = 0;

			switch ($request->action) {
				case "activate":
				case "deactivate":
					$count = $this->budgetService->bulkUpdate($request->budget_ids, [
						"is_active" => $request->action === "activate",
					]);
					break;

				case "delete":
					Budget::whereIn("id", $request->budget_ids)
						->where("user_id", auth()->id())
						->delete();
					$count = count($request->budget_ids);
					break;
			}

			return response()->json([
				"success" => true,
				"message" => "{$count} budget berhasil diperbarui",
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
	 * Dashboard summary
	 */
	public function dashboardSummary()
	{
		$summary = $this->budgetService->getDashboardSummary();

		return response()->json($summary);
	}

	/**
	 * Get budgets for category
	 */
	public function byCategory($categoryId)
	{
		$category = \Modules\Wallet\Models\Category::where(
			"user_id",
			auth()->id()
		)->findOrFail($categoryId);

		$budgets = $this->budgetService->getBudgetsForCategory($category);

		return response()->json([
			"budgets" => $budgets,
		]);
	}

	/**
	 * Get expiring budgets
	 */
	public function expiring()
	{
		$budgets = $this->budgetService->getExpiringBudgets(7);

		return response()->json([
			"budgets" => $budgets,
		]);
	}

	/**
	 * Duplicate budget
	 */
	public function duplicate(Request $request, Budget $budget)
	{
		try {
			$request->validate([
				"name" => "required|string|max:100",
				"duplicate_settings" => "boolean",
				"duplicate_next_period" => "boolean",
			]);

			$user = auth()->user();

			// Check authorization
			if ($budget->user_id !== $user->id) {
				abort(403);
			}

			$newBudget = $budget->replicate();
			$newBudget->name = $request->name;
			$newBudget->spent = 0;

			// If duplicate for next period
			if ($request->boolean("duplicate_next_period")) {
				$nextPeriod = $budget->getNextPeriod();
				$newBudget->start_date = $nextPeriod->start_date;
				$newBudget->end_date = $nextPeriod->end_date;
				$newBudget->period_value = $nextPeriod->period_value;
				$newBudget->year = $nextPeriod->year;
			}

			$newBudget->save();

			// Duplicate accounts if requested
			if ($request->boolean("duplicate_settings")) {
				$accountIds = $budget
					->accounts()
					->pluck("accounts.id")
					->toArray();
				$newBudget->accounts()->sync($accountIds);
			}

			return redirect()
				->route("apps.budgets.show", $newBudget)
				->with("success", "Budget berhasil diduplikasi");
		} catch (\Exception $e) {
			return back()->with("error", $e->getMessage());
		}
	}

	/**
	 * Reset spent amount
	 */
	public function resetSpent(Request $request, Budget $budget)
	{
		try {
			$user = auth()->user();

			// Check authorization
			if ($budget->user_id !== $user->id) {
				abort(403);
			}

			$budget->spent = 0;
			$budget->save();

			return redirect()
				->route("apps.budgets.edit", $budget)
				->with("success", "Jumlah terpakai berhasil direset");
		} catch (\Exception $e) {
			return back()->with("error", $e->getMessage());
		}
	}

	/**
	 * Get next period dates
	 */
	private function getNextPeriodDates(Request $request, Budget $budget)
	{
		try {
			$nextPeriod = $this->budgetService->calculateNextPeriod($budget);

			dd($nextPeriod);
		} catch (\Exception $e) {
			return $request->wantsJson
				? response()->json(
					["success" => false, "message" => $e->getMessage()],
					500
				)
				: back()->withErrors($e->getMessage());
		}
	}
}
