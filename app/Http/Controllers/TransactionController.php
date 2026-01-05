<?php

namespace Modules\Wallet\Http\Controllers;

use Modules\Wallet\Repositories\{
	TransactionRepository,
	CategoryRepository,
	AccountRepository,
	BudgetRepository
};
use Modules\Wallet\Http\Requests\TransactionRequest;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Wallet\Enums\TransactionType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Brick\Money\Money;

class TransactionController extends BaseController
{
	protected $transactionRepository;
	protected $categoryRepository;
	protected $accountRepository;
	protected $budgetRepository;

	public function __construct(
		TransactionRepository $transactionRepository,
		CategoryRepository $categoryRepository,
		AccountRepository $accountRepository,
		BudgetRepository $budgetRepository
	) {
		$this->transactionRepository = $transactionRepository;
		$this->categoryRepository = $categoryRepository;
		$this->accountRepository = $accountRepository;
		$this->budgetRepository = $budgetRepository;
	}

	/**
	 * Display a listing of transactions with filters
	 */
	public function index(Request $request)
	{
		$user = Auth::user();
		$filters = $request->only([
			"type",
			"category_id",
			"account_id",
			"month",
			"year",
			"search",
		]);

		// Set default month/year if not provided
		if (!isset($filters["month"])) {
			$filters["month"] = date("m");
		}
		if (!isset($filters["year"])) {
			$filters["year"] = date("Y");
		}

		// Get transactions with filters
		$transactions = $this->transactionRepository->getWithFilters(
			$user,
			$filters
		);

		dd($transactions);

		// Get summary for the filtered period
		$summary = $this->transactionRepository->getSummary(
			$user,
			$filters["month"],
			$filters["year"]
		);

		// Get categories and accounts for filter dropdowns
		$categories = $this->categoryRepository->getForDropdown($user);
		$accounts = $this->accountRepository->getForDropdown($user);

		// Get months and years for filter
		$months = $this->getMonths();
		$years = $this->getYears();

		return view(
			"wallet::transactions.index",
			compact(
				"transactions",
				"summary",
				"filters",
				"categories",
				"accounts",
				"months",
				"years"
			)
		);
	}

	/**
	 * Show the form for creating a new transaction
	 */
	public function create(Request $request)
	{
		$user = Auth::user();

		// Get preset values from query parameters (for quick add from FAB)
		$preset = [
			"type" => $request->get("type", "expense"),
			"category_id" => $request->get("category_id"),
			"account_id" => $request->get("account_id"),
			"amount" => $request->get("amount"),
			"title" => $request->get("title"),
		];

		$incomeCategories = $this->categoryRepository->getByType("income", $user);
		$expenseCategories = $this->categoryRepository->getByType("expense", $user);
		$accounts = $this->accountRepository->getAccountsMapping(
			$this->accountRepository->accounts($user)
		);

		return view(
			"wallet::transactions.create",
			compact("incomeCategories", "expenseCategories", "accounts", "preset")
		);
	}

	/**
	 * Store a newly created transaction
	 */
	public function store(TransactionRequest $request)
	{
		try {
			$user = Auth::user();
			$data = $request->validated();

			// Check if category belongs to user
			$category = $this->categoryRepository->find($data["category_id"]);
			if ($category->user_id !== $user->id) {
				return redirect()
					->back()
					->withInput()
					->withErrors(["category_id" => "Kategori tidak valid"]);
			}

			// Check if account belongs to user
			$account = $this->accountRepository->find($data["account_id"]);
			if ($account->user_id !== $user->id) {
				return redirect()
					->back()
					->withInput()
					->withErrors(["account_id" => "Akun tidak valid"]);
			}

			// For expense, check account balance
			if ($data["type"] === TransactionType::EXPENSE) {
				$amount = Money::of($data["amount"], $account->currency);
				if (
					!$this->accountRepository->hasSufficientBalance($account->id, $amount)
				) {
					return redirect()
						->back()
						->withInput()
						->withErrors(["amount" => "Saldo akun tidak mencukupi"]);
				}
			}

			$transaction = $this->transactionRepository->createTransaction(
				$data,
				$user
			);

			// Update budget if expense
			if ($data["type"] === TransactionType::EXPENSE) {
				$this->updateBudget($user, $category->id, $data["amount"]);
			}

			return redirect()
				->route("apps.transactions.index")
				->with("success", "Transaksi berhasil ditambahkan");
		} catch (\Exception $e) {
			logger()->error("Error saving transaction", [
				"message" => $e->getMessage(),
				"trace" => $e->getTrace(),
			]);

			throw $e;
			return redirect()
				->back()
				->withInput()
				->with("error", "Gagal menambahkan transaksi: " . $e->getMessage());
		}
	}

	/**
	 * Display the specified transaction
	 */
	public function show($id)
	{
		$user = Auth::user();
		$transaction = $this->transactionRepository->find($id);

		if (!$transaction || $transaction->user_id !== $user->id) {
			abort(404, "Transaksi tidak ditemukan");
		}

		return view("wallet::transactions.show", compact("transaction"));
	}

	/**
	 * Show the form for editing the specified transaction
	 */
	public function edit($id)
	{
		$user = Auth::user();
		$transaction = $this->transactionRepository->find($id);

		if (!$transaction || $transaction->user_id !== $user->id) {
			abort(404, "Transaksi tidak ditemukan");
		}

		$incomeCategories = $this->categoryRepository->getByType("income", $user);
		$expenseCategories = $this->categoryRepository->getByType("expense", $user);
		$accounts = $this->accountRepository->getActiveAccounts($user);

		return view(
			"wallet::transactions.edit",
			compact(
				"transaction",
				"incomeCategories",
				"expenseCategories",
				"accounts"
			)
		);
	}

	/**
	 * Update the specified transaction
	 */
	public function update(TransactionRequest $request, $id)
	{
		try {
			$user = Auth::user();
			$transaction = $this->transactionRepository->find($id);

			if (!$transaction || $transaction->user_id !== $user->id) {
				abort(404, "Transaksi tidak ditemukan");
			}

			$data = $request->validated();

			// Validate category and account if changed
			if (
				isset($data["category_id"]) &&
				$data["category_id"] != $transaction->category_id
			) {
				$category = $this->categoryRepository->find($data["category_id"]);
				if ($category->user_id !== $user->id) {
					return redirect()
						->back()
						->withInput()
						->withErrors(["category_id" => "Kategori tidak valid"]);
				}
			}

			if (
				isset($data["account_id"]) &&
				$data["account_id"] != $transaction->account_id
			) {
				$account = $this->accountRepository->find($data["account_id"]);
				if ($account->user_id !== $user->id) {
					return redirect()
						->back()
						->withInput()
						->withErrors(["account_id" => "Akun tidak valid"]);
				}
			}

			// For expense, check account balance if amount or account changed
			if (
				$data["type"] === "expense" &&
				($data["amount"] != $transaction->amount ||
					$data["account_id"] != $transaction->account_id)
			) {
				$account = $this->accountRepository->find($data["account_id"]);
				$amount = Money::of($data["amount"], "IDR");

				// Calculate the net change needed
				$oldAmount = Money::ofMinor($transaction->amount, "IDR");
				$newAmount = $amount;
				$difference = $newAmount->minus($oldAmount);

				// If increasing expense or changing to a different account, check balance
				if (
					$difference->isPositive() ||
					$data["account_id"] != $transaction->account_id
				) {
					if (
						!$this->accountRepository->hasSufficientBalance(
							$account->id,
							$difference->abs()
						)
					) {
						return redirect()
							->back()
							->withInput()
							->withErrors([
								"amount" => "Saldo akun tidak mencukupi untuk perubahan ini",
							]);
					}
				}
			}

			$updatedTransaction = $this->transactionRepository->updateTransaction(
				$id,
				$data
			);

			// Update budgets if expense
			if ($data["type"] === "expense") {
				// Update old category budget if category changed
				if ($transaction->category_id != $updatedTransaction->category_id) {
					$this->updateBudget(
						$user,
						$transaction->category_id,
						-$transaction->amount
					);
				}

				// Update new category budget
				$this->updateBudget(
					$user,
					$updatedTransaction->category_id,
					$data["amount"]
				);
			}

			return redirect()
				->route("apps.transactions.index")
				->with("success", "Transaksi berhasil diperbarui");
		} catch (\Exception $e) {
			return redirect()
				->back()
				->withInput()
				->with("error", "Gagal memperbarui transaksi: " . $e->getMessage());
		}
	}

	/**
	 * Remove the specified transaction
	 */
	public function destroy($id)
	{
		try {
			$user = Auth::user();
			$transaction = $this->transactionRepository->find($id);

			if (!$transaction || $transaction->user_id !== $user->id) {
				abort(404, "Transaksi tidak ditemukan");
			}

			$this->transactionRepository->deleteTransaction($id);

			// Update budget if expense
			if ($transaction->type === "expense") {
				$this->updateBudget(
					$user,
					$transaction->category_id,
					-$transaction->amount
				);
			}

			return redirect()
				->route("transactions.index")
				->with("success", "Transaksi berhasil dihapus");
		} catch (\Exception $e) {
			return redirect()
				->back()
				->with("error", "Gagal menghapus transaksi: " . $e->getMessage());
		}
	}

	/**
	 * Get transactions by type (income/expense)
	 */
	public function byType($type)
	{
		$user = Auth::user();

		if (!in_array($type, ["income", "expense"])) {
			abort(404);
		}

		$transactions = $this->transactionRepository->getByType($user, $type);
		$categories = $this->categoryRepository->getByType($type, $user);
		$accounts = $this->accountRepository->getActiveAccounts($user);

		$summary = $this->transactionRepository->getSummary($user);

		return view(
			"transactions.by-type",
			compact("transactions", "type", "categories", "accounts", "summary")
		);
	}

	/**
	 * Update budget for category
	 */
	private function updateBudget($user, $categoryId, $amount)
	{
		$budget = $this->budgetRepository
			->getModel()
			->where("user_id", $user->id)
			->where("category_id", $categoryId)
			->where("month", date("m"))
			->where("year", date("Y"))
			->first();

		if ($budget) {
			$this->budgetRepository->updateSpentAmount($budget->id);
		}
	}

	/**
	 * Get months for filter
	 */
	private function getMonths(): array
	{
		return [
			"01" => "Januari",
			"02" => "Februari",
			"03" => "Maret",
			"04" => "April",
			"05" => "Mei",
			"06" => "Juni",
			"07" => "Juli",
			"08" => "Agustus",
			"09" => "September",
			"10" => "Oktober",
			"11" => "November",
			"12" => "Desember",
		];
	}

	/**
	 * Get years for filter (last 5 years and next year)
	 */
	private function getYears(): array
	{
		$currentYear = date("Y");
		$years = [];

		for ($i = 5; $i >= 1; $i--) {
			$year = $currentYear - $i;
			$years[$year] = $year;
		}

		$years[$currentYear] = $currentYear;
		$years[$currentYear + 1] = $currentYear + 1;

		return $years;
	}
}
