<?php

namespace Modules\Wallet\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Wallet\Services\RecurringTransactionService;
use Modules\Wallet\Services\AccountService;
use Modules\Wallet\Services\CategoryService;
use Modules\Wallet\Http\Requests\RecurringTransactionRequest;

class RecurringController extends Controller
{
	protected $recurringService;
	protected $accountService;
	protected $categoryService;

	public function __construct(
		RecurringTransactionService $recurringService,
		AccountService $accountService,
		CategoryService $categoryService
	) {
		$this->recurringService = $recurringService;
		$this->accountService = $accountService;
		$this->categoryService = $categoryService;
	}

	/**
	 * Display a listing of recurring transactions
	 */
	public function index(Request $request)
	{
		$filters = $request->only(["status", "type", "frequency", "search"]);
		$perPage = $request->get("per_page", 20);

		try {
			$data = $this->recurringService->getPaginatedRecurringTransactions(
				$filters,
				$perPage
			);

			return view("wallet::recurring.index", [
				"recurringTransactions" => $data["transactions"],
				"stats" => $data["stats"],
				"filters" => $filters,
			]);
		} catch (\Exception $e) {
			return redirect()
				->back()
				->withErrors(
					"Failed to load recurring transactions: " . $e->getMessage()
				);
		}
	}

	/**
	 * Show the form for creating a new recurring transaction
	 */
	public function create()
	{
		try {
			$accounts = $this->accountService
				->getRepository()
				->getUserAccounts(auth()->user(), ["is_active" => true]);
			$categories = $this->categoryService->getCategoriesForDropdown("expense");

			$frequencies = [
				"daily" => "Harian",
				"weekly" => "Mingguan",
				"monthly" => "Bulanan",
				"quarterly" => "Triwulan",
				"yearly" => "Tahunan",
			];

			return view(
				"wallet::recurring.create",
				compact("accounts", "categories", "frequencies")
			);
		} catch (\Exception $e) {
			return redirect()
				->route("apps.recurrings.index")
				->withErrors("Failed to load form: " . $e->getMessage());
		}
	}

	/**
	 * Store a newly created recurring transaction
	 */
	public function store(RecurringTransactionRequest $request)
	{
		try {
			$data = $request->validated();
			$data["user_id"] = auth()->id();

			$recurringTransaction = $this->recurringService->createRecurringTransaction(
				$data
			);

			return redirect()
				->route("apps.recurrings.index")
				->with("success", "Transaksi rutin berhasil ditambahkan.");
		} catch (\Exception $e) {
			return redirect()
				->back()
				->withInput()
				->withErrors("Gagal menambahkan transaksi rutin: " . $e->getMessage());
		}
	}

	/**
	 * Display the specified recurring transaction
	 */
	public function show($id)
	{
		try {
			$recurringTransaction = $this->recurringService->findRecurringTransaction(
				(int) $id
			);

			if (!$recurringTransaction) {
				return redirect()
					->route("apps.recurring.index")
					->withErrors("Transaksi rutin tidak ditemukan.");
			}

			// Get related transactions
			$relatedTransactions = $recurringTransaction
				->transactions()
				->orderBy("transaction_date", "desc")
				->paginate(10);

			return view(
				"wallet::recurring.show",
				compact("recurringTransaction", "relatedTransactions")
			);
		} catch (\Exception $e) {
			return redirect()
				->route("apps.recurrings.index")
				->withErrors("Gagal memuat detail: " . $e->getMessage());
		}
	}

	/**
	 * Show the form for editing the specified recurring transaction
	 */
	public function edit($id)
	{
		try {
			$recurringTransaction = $this->recurringService->findRecurringTransaction(
				(int) $id
			);

			if (!$recurringTransaction) {
				return redirect()
					->route("apps.recurrings.index")
					->withErrors("Transaksi rutin tidak ditemukan.");
			}

			$accounts = $this->accountService
				->getRepository()
				->getUserAccounts(auth()->user(), ["is_active" => true]);
			$categories = $this->categoryService->getCategoriesForDropdown("expense");

			$frequencies = [
				"daily" => "Harian",
				"weekly" => "Mingguan",
				"monthly" => "Bulanan",
				"quarterly" => "Triwulan",
				"yearly" => "Tahunan",
			];

			return view(
				"wallet::recurring.edit",
				compact("recurringTransaction", "accounts", "categories", "frequencies")
			);
		} catch (\Exception $e) {
			return redirect()
				->route("apps.recurrings.index")
				->withErrors("Gagal memuat form edit: " . $e->getMessage());
		}
	}

	/**
	 * Update the specified recurring transaction
	 */
	public function update(RecurringTransactionRequest $request, $id)
	{
		try {
			$recurringTransaction = $this->recurringService->findRecurringTransaction(
				(int) $id
			);

			if (!$recurringTransaction) {
				return redirect()
					->route("apps.recurrings.index")
					->withErrors("Transaksi rutin tidak ditemukan.");
			}

			$data = $request->validated();
			$this->recurringService->updateRecurringTransaction(
				$recurringTransaction,
				$data
			);

			return redirect()
				->route("apps.recurrings.index")
				->with("success", "Transaksi rutin berhasil diperbarui.");
		} catch (\Exception $e) {
			return redirect()
				->back()
				->withInput()
				->withErrors("Gagal memperbarui transaksi rutin: " . $e->getMessage());
		}
	}

	/**
	 * Remove the specified recurring transaction
	 */
	public function destroy($id)
	{
		try {
			$recurringTransaction = $this->recurringService->findRecurringTransaction(
				$id
			);

			if (!$recurringTransaction) {
				return redirect()
					->route("apps.recurrings.index")
					->withErrors("Transaksi rutin tidak ditemukan.");
			}

			$this->recurringService->deleteRecurringTransaction(
				$recurringTransaction
			);

			return redirect()
				->route("apps.recurrings.index")
				->with("success", "Transaksi rutin berhasil dihapus.");
		} catch (\Exception $e) {
			return redirect()
				->route("apps.recurrings.index")
				->withErrors("Gagal menghapus transaksi rutin: " . $e->getMessage());
		}
	}

	/**
	 * Toggle status of recurring transaction
	 */
	public function toggleStatus($id)
	{
		try {
			$recurringTransaction = $this->recurringService->findRecurringTransaction(
				$id
			);

			if (!$recurringTransaction) {
				return response()->json(
					["success" => false, "message" => "Transaksi rutin tidak ditemukan."],
					404
				);
			}

			$recurringTransaction = $this->recurringService->toggleStatus(
				$recurringTransaction
			);

			return response()->json([
				"success" => true,
				"message" => "Status berhasil diubah.",
				"is_active" => $recurringTransaction->is_active,
			]);
		} catch (\Exception $e) {
			return response()->json(
				[
					"success" => false,
					"message" => "Gagal mengubah status: " . $e->getMessage(),
				],
				500
			);
		}
	}

	/**
	 * Process due recurring transactions manually
	 */
	public function processDue()
	{
		try {
			$result = $this->recurringService->processDueRecurringTransactions();

			$message =
				"Diproses: {$result["processed"]}, " .
				"Dilewati: {$result["skipped"]}, " .
				"Error: " .
				count($result["errors"]);

			return redirect()
				->route("apps.recurrings.index")
				->with("success", $message);
		} catch (\Exception $e) {
			return redirect()
				->route("apps.recurrings.index")
				->withErrors("Gagal memproses transaksi rutin: " . $e->getMessage());
		}
	}

	/**
	 * Get upcoming recurring transactions (AJAX)
	 */
	public function upcoming(Request $request)
	{
		try {
			$days = $request->get("days", 30);
			$upcoming = $this->recurringService->getUpcomingTransactions($days);

			return response()->json([
				"success" => true,
				"data" => $upcoming,
			]);
		} catch (\Exception $e) {
			return response()->json(
				[
					"success" => false,
					"message" =>
						"Gagal mengambil data transaksi mendatang: " . $e->getMessage(),
				],
				500
			);
		}
	}

	/**
	 * Export recurring transactions
	 */
	public function export(Request $request)
	{
		try {
			$filters = $request->only(["status", "type", "frequency", "search"]);
			$data = $this->recurringService->getRecurringTransactionsForExport(
				$filters
			);

			return response()->json([
				"success" => true,
				"data" => $data,
			]);
		} catch (\Exception $e) {
			return response()->json(
				[
					"success" => false,
					"message" => "Gagal mengekspor data: " . $e->getMessage(),
				],
				500
			);
		}
	}

	/**
	 * Preview next occurrences
	 */
	public function previewNextOccurrences($id)
	{
		try {
			$recurringTransaction = $this->recurringService->findRecurringTransaction(
				(int) $id
			);

			if (!$recurringTransaction) {
				return response()->json(
					["success" => false, "message" => "Transaksi rutin tidak ditemukan."],
					404
				);
			}

			$occurrences = $this->recurringService->getNextOccurrences(
				$recurringTransaction,
				10
			);

			return response()->json([
				"success" => true,
				"occurrences" => $occurrences,
			]);
		} catch (\Exception $e) {
			return response()->json(
				[
					"success" => false,
					"message" => "Gagal memuat data: " . $e->getMessage(),
				],
				500
			);
		}
	}

	public function bulkUpdate(Request $request)
	{
	}
}
