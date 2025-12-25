<?php

namespace Modules\Wallet\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Models\Account;
use Modules\Wallet\Helpers\Helper;
use Modules\Wallet\Services\TransactionService;
use Modules\Wallet\Repositories\AccountRepository;
use Modules\Wallet\Repositories\WalletRepository;
use Modules\Wallet\Http\Requests\WalletRequest;

class WalletController extends Controller
{
	protected $accountRepository;
	protected $walletRepository;
	protected $transactionService;

	public function __construct(
		AccountRepository $accountRepository,
		WalletRepository $walletRepository,
		TransactionService $transactionService
	) {
		$this->accountRepository = $accountRepository;
		$this->walletRepository = $walletRepository;
		$this->transactionService = $transactionService;
	}

	/**
	 * Display a listing of wallets for the authenticated user
	 */
	public function index(Request $request)
	{
		try {
			$wallets = $this->walletRepository->getUserWallets($request->all());
			$accounts = $this->accountRepository->getUserAccounts();
			$currencies = Helper::listCurrencies();

			return view(
				"wallet::wallets.index",
				compact("wallets", "accounts", "currencies")
			);
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
	 * Store a newly created wallet for a specific account
	 */
	public function store(WalletRequest $request)
	{
		try {
			dd($this->accountRepository->getDefaultUserAccount());
			$wallet = $this->walletRepository->createWallet(
				$account,
				$request->validated()
			);

			return response()->json(
				[
					"success" => true,
					"message" => "Wallet created successfully",
					"data" => $wallet->load("account"),
				],
				201
			);
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
	 * Display the specified wallet
	 */
	public function show(Wallet $wallet)
	{
		$this->authorize("view", $wallet);

		try {
			$wallet->load([
				"account",
				"transactions" => function ($query) {
					$query->latest()->limit(10);
				},
			]);

			return response()->json([
				"success" => true,
				"data" => $wallet,
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
	 * Update the specified wallet
	 */
	public function update(WalletRequest $request, Wallet $wallet)
	{
		$this->authorize("update", $wallet);

		try {
			$wallet = $this->walletRepository->updateWallet(
				$wallet,
				$request->validated()
			);

			return response()->json([
				"success" => true,
				"message" => "Wallet updated successfully",
				"data" => $wallet,
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
	 * Remove the specified wallet
	 */
	public function destroy(Wallet $wallet)
	{
		$this->authorize("delete", $wallet);

		try {
			$this->walletRepository->deleteWallet($wallet);

			return response()->json([
				"success" => true,
				"message" => "Wallet deleted successfully",
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
	 * Get transactions for a specific wallet
	 */
	public function transactions(Request $request, Wallet $wallet)
	{
		$this->authorize("view", $wallet);

		try {
			$transactions = $wallet
				->transactions()
				->with(["toWallet", "toAccount"])
				->orderBy("transaction_date", "desc")
				->orderBy("created_at", "desc")
				->paginate($request->per_page ?? 15);

			return response()->json([
				"success" => true,
				"data" => $transactions,
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
	 * Get current balance of a wallet
	 */
	public function balance(Wallet $wallet)
	{
		$this->authorize("view", $wallet);

		try {
			return response()->json([
				"success" => true,
				"data" => [
					"balance" => $wallet->balance,
					"formatted_balance" => $wallet->formatted_balance,
					"currency" => $wallet->currency,
					"initial_balance" => $wallet->initial_balance,
					"formatted_initial_balance" => $wallet->formatted_initial_balance,
				],
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
	 * Get wallet statement for a period
	 */
	public function statement(Request $request, Wallet $wallet)
	{
		$this->authorize("view", $wallet);

		$request->validate([
			"start_date" => "required|date",
			"end_date" => "required|date|after_or_equal:start_date",
		]);

		try {
			$statement = app(
				\Modules\Wallet\Services\ReportService::class
			)->getWalletStatement(
				$wallet->id,
				$request->start_date,
				$request->end_date
			);

			return response()->json([
				"success" => true,
				"data" => $statement,
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
