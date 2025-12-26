<?php

namespace Modules\Wallet\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Wallet\Models\Transaction;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Constants\Permissions;
use Modules\Wallet\Enums\CategoryType;
use Modules\Wallet\Services\TransactionService;
use Modules\Wallet\Repositories\CategoryRepository;
use Modules\Wallet\Repositories\TransactionRepository;
use Modules\Wallet\Repositories\WalletRepository;
use Modules\Wallet\Http\Requests\TransactionRequest;
use Modules\Wallet\Http\Requests\TransferRequest;

class TransactionController extends BaseController
{
	protected $walletRepository;
	protected $transactionService;
	protected $categoryRepository;
	protected $transactionRepository;

	public function __construct(
		TransactionService $transactionService,
		TransactionRepository $transactionRepository,
		WalletRepository $walletRepository,
		CategoryRepository $categoryRepository
	) {
		$this->transactionService = $transactionService;
		$this->categoryRepository = $categoryRepository;
		$this->transactionRepository = $transactionRepository;
		$this->walletRepository = $walletRepository;

		if ($this->isPermissionMiddlewareExists()) {
			$this->middleware("permission:" . Permissions::VIEW_TRANSACTIONS)->only([
				"index",
				"show",
			]);
			$this->middleware("permission:" . Permissions::CREATE_TRANSACTIONS)->only(
				["create", "store"]
			);
			$this->middleware("permission:" . Permissions::EDIT_TRANSACTIONS)->only([
				"edit",
				"store",
			]);
		}
	}

	public function index(Request $request)
	{
		$transactions = $this->transactionRepository->getUserTransactions(
			$request->all()
		);
		dd(
			$transactions,
			$transactions->map(
				fn($item) => [
					"total" => $item->count(),
					"deposit" => $item
						->filter(fn($i) => $i->type == CategoryType::INCOME)
						->count(),
					"withdraw" => $item
						->filter(fn($i) => $i->type == CategoryType::EXPENSE)
						->count(),
				]
			)
		);

		return view("wallet::transactions.index", compact("transactions"));
	}

	public function create()
	{
		$wallet = $this->walletRepository->getDefaultUserWallet();
		$depositCategories = $this->categoryRepository->getUserCategories(
			CategoryType::INCOME
		);
		$withdrawCategories = $this->categoryRepository->getUserCategories(
			CategoryType::EXPENSE
		);

		return view(
			"wallet::transactions.create",
			compact("wallet", "depositCategories", "withdrawCategories")
		);
	}

	public function store(TransactionRequest $request)
	{
		try {
			$transaction = $this->transactionService->recordTransaction(
				$request->validated()
			);

			return response()->json(
				[
					"success" => true,
					"message" => "Transaction recorded successfully",
					"data" => $transaction->load(["wallet", "toWallet"]),
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

	public function show(Transaction $transaction)
	{
		$this->authorize("view", $transaction);

		$transaction->load(["wallet", "toWallet", "toAccount", "user"]);

		return response()->json([
			"success" => true,
			"data" => $transaction,
		]);
	}

	public function update(TransactionRequest $request, Transaction $transaction)
	{
		$this->authorize("update", $transaction);

		try {
			$transaction = $this->transactionRepository->updateTransaction(
				$transaction,
				$request->validated()
			);

			return response()->json([
				"success" => true,
				"message" => "Transaction updated successfully",
				"data" => $transaction,
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

	public function transfer(TransferRequest $request)
	{
		try {
			$fromWallet = Wallet::findOrFail($request->from_wallet_id);
			$toWallet = Wallet::findOrFail($request->to_wallet_id);

			$this->authorize("transfer", [$fromWallet, $toWallet]);

			$transactions = $this->transactionService->transfer(
				$fromWallet,
				$toWallet,
				$request->validated()
			);

			return response()->json(
				[
					"success" => true,
					"message" => "Transfer completed successfully",
					"data" => $transactions,
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

	public function updateStatus(Request $request, Transaction $transaction)
	{
		$this->authorize("update", $transaction);

		$request->validate([
			"status" => "required|in:pending,completed,failed,cancelled",
			"notes" => "nullable|string",
		]);

		try {
			$transaction = $this->transactionService->updateTransactionStatus(
				$transaction,
				$request->status,
				["notes" => $request->notes]
			);

			return response()->json([
				"success" => true,
				"message" => "Transaction status updated successfully",
				"data" => $transaction,
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

	public function reconcile(Request $request, Transaction $transaction)
	{
		$this->authorize("update", $transaction);

		$request->validate([
			"is_reconciled" => "required|boolean",
		]);

		try {
			$transaction->update([
				"is_reconciled" => $request->is_reconciled,
				"reconciled_at" => $request->is_reconciled ? now() : null,
				"reconciled_by" => $request->is_reconciled ? auth()->id() : null,
			]);

			return response()->json([
				"success" => true,
				"message" => "Transaction reconciled successfully",
				"data" => $transaction,
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

	public function summary(Request $request)
	{
		$summary = $this->transactionRepository->getTransactionSummary(
			auth()->id(),
			$request->start_date,
			$request->end_date
		);

		return response()->json([
			"success" => true,
			"data" => $summary,
		]);
	}

	public function recent()
	{
		$transactions = $this->transactionRepository->getRecentTransactions(10);

		return response()->json([
			"success" => true,
			"data" => $transactions,
		]);
	}
}
