<?php

namespace Modules\Wallet\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Helpers\Helper;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Wallet\Constants\Permissions;
use Modules\Wallet\Services\TransactionService;
use Modules\Wallet\Repositories\WalletRepository;
use Modules\Wallet\Http\Requests\DepositRequest;
use Modules\Wallet\Http\Requests\WalletRequest;

class WalletController extends BaseController
{
	protected $walletRepository;
	protected $transactionService;

	public function __construct(
		WalletRepository $walletRepository,
		TransactionService $transactionService
	) {
		$this->walletRepository = $walletRepository;
		$this->transactionService = $transactionService;

		if ($this->isPermissionMiddlewareExists()) {
			$this->middleware("permission:" . Permissions::VIEW_WALLETS)->only([
				"index",
				"show",
			]);
			$this->middleware("permission:" . Permissions::CREATE_WALLETS)->only([
				"store",
			]);
			$this->middleware("permission:" . Permissions::EDIT_WALLETS)->only([
				"edit",
				"update",
			]);
			$this->middleware("permission:" . Permissions::DEPOSIT_WALLETS)->only([
				"deposit",
			]);
		}
	}

	/**
	 * Display a listing of wallets for the authenticated user
	 */
	public function index(Request $request)
	{
		try {
			$wallets = $this->walletRepository->getUserWallets($request->all());
			$currencies = Helper::listCurrencies();

			return view("wallet::wallets.index", compact("wallets", "currencies"));
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
			$wallet = $this->walletRepository->createWallet($request->validated());

			return redirect()
				->route("apps.wallets.index")
				->with("success", "Created wallet successfully");
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
		try {
			$wallet->load([
				"transactions" => function ($query) {
					$query->latest()->limit(10);
				},
			]);

			return view("wallet::wallets.show", compact("wallet"));
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

	public function edit(Request $request, Wallet $wallet)
	{
		$currencies = collect(config("money.currencies"))
			->keys()
			->mapWithKeys(
				fn($currency) => [
					$currency =>
						config("money.currencies")[$currency]["name"] .
						" (" .
						config("money.currencies")[$currency]["symbol"] .
						")",
				]
			)
			->toArray();

		return view("wallet::wallets.edit", compact("wallet", "currencies"));
	}

	/**
	 * Update the specified wallet
	 */
	public function update(WalletRequest $request, Wallet $wallet)
	{
		try {
			$wallet = $this->walletRepository->updateWallet(
				$wallet,
				$request->validated()
			);

			return redirect()
				->route("apps.wallets.index")
				->with("success", "Wallet updated successfully");
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
		try {
			$this->walletRepository->deleteWallet($wallet);

			return redirect()
				->route("apps.wallets.index")
				->with("success", "Wallet deleted successfully");
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

	public function setDefault(Request $request)
	{
		$request->validate(["wallet_id" => "required|exists:wallets,id"]);

		$wallet = Wallet::findOrFail((int) $request->wallet_id);
		if ($wallet->exists()) {
			$wallet->update(["is_default" => true]);

			return back()->with("success", "Wallet default set successfully");
		}

		return back()->withErrors("Wallet not found.");
	}

	public function deposit(DepositRequest $request, Wallet $wallet)
	{
		try {
			$this->transactionService->deposit($wallet, $request->validated());

			return back()->with("success", "Deposit wallet successfully.");
		} catch (\Exception $e) {
			return response()->json([
				"error" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
			]);
		}
	}

	public function withdraw(Request $request, Wallet $wallet)
	{
	}
}
