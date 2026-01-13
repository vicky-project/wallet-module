<?php

namespace Modules\Wallet\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Wallet\Services\TransactionService;
use Modules\Wallet\Repositories\AccountRepository;
use Modules\Wallet\Repositories\CategoryRepository;
use Modules\Wallet\Repositories\TransactionRepository;
use Modules\Wallet\Enums\TransactionType;

class TransactionController extends Controller
{
	protected $transactionService;
	protected $accountRepository;
	protected $categoryRepository;
	protected $transactionRepository;

	public function __construct(
		TransactionService $transactionService,
		AccountRepository $accountRepository,
		CategoryRepository $categoryRepository,
		TransactionRepository $transactionRepository
	) {
		$this->transactionService = $transactionService;
		$this->accountRepository = $accountRepository;
		$this->categoryRepository = $categoryRepository;
		$this->transactionRepository = $transactionRepository;

		$this->middleware("auth");
	}

	/**
	 * Display a listing of transactions.
	 */
	public function index(Request $request)
	{
		$user = Auth::user();

		// Get accounts and categories for filters
		$accounts = $this->accountRepository->getUserAccounts($user, [
			"is_active" => true,
		]);
		$categories = $this->categoryRepository->getUserCategories();

		// Apply filters
		$filters = $request->only([
			"type",
			"account_id",
			"category_id",
			"description",
			"start_date",
			"end_date",
			"payment_method",
			"search",
		]);

		// Get paginated transactions
		$result = $this->transactionService->getPaginatedTransactions($filters, 20);

		if (!$result["success"]) {
			return redirect()
				->back()
				->with("error", $result["message"]);
		}

		return view("wallet::transactions.index", [
			"transactions" => $result["transactions"],
			"accounts" => $accounts,
			"categories" => $categories,
			"totals" => $result["totals"] ?? [],
		]);
	}

	/**
	 * Show the form for creating a new transaction.
	 */
	public function create(Request $request)
	{
		$user = Auth::user();

		$accounts = $this->accountRepository->getUserAccounts($user, [
			"is_active" => true,
		]);
		$categories = $this->categoryRepository->getUserCategories();

		// Filter categories by type if specified
		$type = $request->get("type", TransactionType::EXPENSE->value);
		if (in_array($type, TransactionType::all())) {
			$categories = $categories->where("type", $type);
		}

		// Get today's transaction count
		$todayTransactions = $this->transactionRepository
			->query()
			->where("user_id", $user->id)
			->whereDate("transaction_date", today())
			->count();

		return view("wallet::transactions.form", [
			"transaction" => null,
			"accounts" => $accounts,
			"categories" => $categories,
			"todayTransactions" => $todayTransactions,
		]);
	}

	/**
	 * Store a newly created transaction.
	 */
	public function store(Request $request)
	{
		$user = Auth::user();

		$validated = $this->validateTransaction($request);

		$result = $this->transactionService->createTransaction($validated, $user);

		if ($result["success"]) {
			return redirect()
				->route("apps.transactions.index")
				->with("success", $result["message"]);
		} else {
			return redirect()
				->back()
				->withInput()
				->with("error", $result["message"]);
		}
	}

	/**
	 * Show the form for editing the specified transaction.
	 */
	public function edit(string $uuid)
	{
		$user = Auth::user();

		// Get transaction
		$result = $this->transactionService->getTransaction($uuid, $user);

		if (!$result["success"]) {
			return redirect()
				->route("apps.transactions.index")
				->with("error", $result["message"]);
		}

		$transaction = $result["transaction"];

		// Get accounts and categories
		$accounts = $this->accountRepository->getUserAccounts($user, [
			"is_active" => true,
		]);
		$categories = $this->categoryRepository->getUserCategories();

		return view("wallet::transactions.form", [
			"transaction" => $transaction,
			"accounts" => $accounts,
			"categories" => $categories,
		]);
	}

	/**
	 * Update the specified transaction.
	 */
	public function update(Request $request, string $uuid)
	{
		$user = Auth::user();

		$validated = $this->validateTransaction($request, true);

		$result = $this->transactionService->updateTransaction(
			$uuid,
			$validated,
			$user
		);

		if ($result["success"]) {
			return redirect()
				->route("apps.transactions.index")
				->with("success", $result["message"]);
		} else {
			return redirect()
				->back()
				->withInput()
				->with("error", $result["message"]);
		}
	}

	/**
	 * Remove the specified transaction.
	 */
	public function destroy(string $uuid)
	{
		$user = Auth::user();

		$result = $this->transactionService->deleteTransaction($uuid, $user);

		if ($result["success"]) {
			return redirect()
				->route("apps.transactions.index")
				->with("success", $result["message"]);
		} else {
			return redirect()
				->back()
				->with("error", $result["message"]);
		}
	}

	/**
	 * Check budget for transaction.
	 */
	public function checkBudget(Request $request)
	{
		$user = Auth::user();

		$request->validate([
			"category_id" => "required|exists:categories,id",
			"amount" => "required|integer|min:1",
			"date" => "required|date",
		]);

		$category = $this->categoryRepository->find($request->category_id);

		if (!$category) {
			return response()->json(["has_budget" => false]);
		}

		$budgetRepo = app(\Modules\Wallet\Repositories\BudgetRepository::class);
		$budget = $budgetRepo->getActiveBudgetForDate(
			$category,
			$user->id,
			\Carbon\Carbon::parse($request->date)
		);

		if ($budget) {
			// Calculate current spent
			$currentSpent = $budget->spent;
			$newTotal = $currentSpent + $request->amount;

			return response()->json([
				"has_budget" => true,
				"budget_amount" => $budget->amount,
				"current_spent" => $currentSpent,
				"formatted_budget_amount" =>
					"Rp " . number_format($budget->amount, 0, ",", "."),
				"formatted_spent" => "Rp " . number_format($currentSpent, 0, ",", "."),
			]);
		}

		return response()->json(["has_budget" => false]);
	}

	/**
	 * Export transactions.
	 */
	public function export(Request $request)
	{
		$user = Auth::user();

		$result = $this->transactionService->exportTransactions(
			$user,
			$request->get("format", "excel"),
			$request->get("start_date"),
			$request->get("end_date")
		);

		if (!$result["success"]) {
			return redirect()
				->back()
				->with("error", $result["message"]);
		}

		// For now, return JSON (in real implementation, generate file)
		return response()->json($result["data"]);
	}

	/**
	 * Import transactions.
	 */
	public function import(Request $request)
	{
		$request->validate([
			"file" => "required|file|mimes:csv,xlsx,xls,json|max:2048",
		]);

		$user = Auth::user();

		// Parse file based on format
		$file = $request->file("file");
		$extension = $file->getClientOriginalExtension();

		$data = [];

		try {
			if (in_array($extension, ["xlsx", "xls"])) {
				$data = $this->parseExcelFile($file);
			} elseif ($extension === "csv") {
				$data = $this->parseCsvFile($file);
			} elseif ($extension === "json") {
				$data = json_decode(file_get_contents($file->path()), true);
			}

			$result = $this->transactionService->importTransactions(
				$data,
				$user,
				$extension
			);

			if ($result["success"]) {
				$message = $result["message"];
				if (!empty($result["results"]["errors"])) {
					$message .=
						"<br>Error detail: " .
						implode("<br>", $result["results"]["errors"]);
				}

				return redirect()
					->route("apps.transactions.index")
					->with("success", $message);
			} else {
				return redirect()
					->back()
					->with("error", $result["message"]);
			}
		} catch (\Exception $e) {
			return redirect()
				->back()
				->with("error", "Gagal mengimpor file: " . $e->getMessage());
		}
	}

	/**
	 * Duplicate transaction.
	 */
	public function duplicate(string $uuid)
	{
		$user = Auth::user();

		$result = $this->transactionService->duplicateTransaction($uuid, $user);

		if ($result["success"]) {
			return redirect()
				->route("apps.transactions.index")
				->with("success", $result["message"]);
		} else {
			return redirect()
				->back()
				->with("error", $result["message"]);
		}
	}

	/**
	 * Bulk delete transactions.
	 */
	public function bulkDelete(Request $request)
	{
		$request->validate([
			"ids" => "required|array",
			"ids.*" => "integer|exists:transactions,id",
		]);

		$user = Auth::user();

		$result = $this->transactionService->bulkDelete($request->ids, $user);

		if ($result["success"]) {
			return response()->json([
				"success" => true,
				"message" => $result["message"],
				"deleted" => $result["deleted"],
			]);
		} else {
			return response()->json(
				[
					"success" => false,
					"message" => $result["message"],
				],
				400
			);
		}
	}

	/**
	 * Bulk update transactions.
	 */
	public function bulkUpdate(Request $request)
	{
		$request->validate([
			"ids" => "required|array",
			"ids.*" => "integer|exists:transactions,id",
			"field" => "required|string",
			"value" => "required",
		]);

		$user = Auth::user();

		$data = [$request->field => $request->value];
		$result = $this->transactionService->bulkUpdate(
			$request->ids,
			$data,
			$user
		);

		if ($result["success"]) {
			return response()->json([
				"success" => true,
				"message" => $result["message"],
				"updated" => $result["updated"],
			]);
		} else {
			return response()->json(
				[
					"success" => false,
					"message" => $result["message"],
				],
				400
			);
		}
	}

	/**
	 * Get transaction analytics.
	 */
	public function analytics(Request $request)
	{
		$user = Auth::user();

		$period = $request->get("period", "monthly");

		$result = $this->transactionService->getAnalytics($user, $period);

		if ($result["success"]) {
			return response()->json($result["analytics"]);
		} else {
			return response()->json(
				[
					"success" => false,
					"message" => $result["message"],
				],
				400
			);
		}
	}

	/**
	 * Get daily summary.
	 */
	public function dailySummary(Request $request)
	{
		$user = Auth::user();

		$request->validate([
			"start_date" => "required|date",
			"end_date" => "required|date",
		]);

		$result = $this->transactionService->getDailySummary(
			$user,
			$request->start_date,
			$request->end_date
		);

		if ($result["success"]) {
			return response()->json($result);
		} else {
			return response()->json(
				[
					"success" => false,
					"message" => $result["message"],
				],
				400
			);
		}
	}

	/**
	 * Search transactions with advanced filters.
	 */
	public function searchAdvanced(Request $request)
	{
		$filters = $request->all();

		$result = $this->transactionService->searchAdvanced($filters);

		if ($result["success"]) {
			return response()->json($result);
		} else {
			return response()->json(
				[
					"success" => false,
					"message" => $result["message"],
				],
				400
			);
		}
	}

	/**
	 * Validate transaction request.
	 */
	private function validateTransaction(
		Request $request,
		bool $isUpdate = false
	): array {
		$rules = [
			"account_id" => "required|exists:accounts,id",
			"category_id" => "required|exists:categories,id",
			"type" => "required|in:income,expense,transfer",
			"amount" => "required|integer|min:1",
			"description" => "required|string|max:255",
			"transaction_date" => "required|date",
			"notes" => "nullable|string",
			"payment_method" => "nullable|string",
			"reference_number" => "nullable|string|max:100",
			"is_recurring" => "nullable|boolean",
		];

		if ($request->type === "transfer") {
			$rules["to_account_id"] =
				"required|exists:accounts,id|different:account_id";
		}

		return $request->validate($rules);
	}

	/**
	 * Parse Excel file.
	 */
	private function parseExcelFile($file): array
	{
		// Implementation for parsing Excel files
		// You can use Laravel Excel package or PHPExcel
		// This is a simplified version
		$data = [];

		// For now, return empty array (implement based on your Excel library)
		return $data;
	}

	/**
	 * Parse CSV file.
	 */
	private function parseCsvFile($file): array
	{
		$data = [];
		$handle = fopen($file->path(), "r");
		$headers = fgetcsv($handle);

		while (($row = fgetcsv($handle)) !== false) {
			$data[] = array_combine($headers, $row);
		}

		fclose($handle);
		return $data;
	}
}
