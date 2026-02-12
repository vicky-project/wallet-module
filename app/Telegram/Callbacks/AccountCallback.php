<?php
namespace Modules\Wallet\Telegram\Callbacks;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Number;
use Modules\Wallet\Helpers\Helper;
use Modules\Wallet\Models\Account;
use Modules\Wallet\Enums\TransactionType;
use Modules\Wallet\Services\AccountService;
use Modules\Telegram\Services\Support\InlineKeyboardBuilder;

class AccountCallback
{
	protected AccountService $service;
	protected $repo;
	protected $keyboard;

	public function __construct(
		AccountService $service,
		InlineKeyboardBuilder $keyboard
	) {
		$this->service = $service;
		$this->repo = $this->service->getRepository();
		$this->keyboard = $keyboard;
	}

	public function action(
		User $user,
		string $action,
		int $id, // may be account id or user id
		array $params = []
	) {
		try {
			$account = $this->repo->find($id);
			if (!$account && $action === "create") {
				return $this->createAccount($user, $params);
			} else {
				return [
					"success" => false,
					"status" => "account_not_found",
					"answer" => "Account not found. Please create account first",
					"show_alert" => true,
				];
			}

			$this->service->validateAccount($account, $user);

			switch ($action) {
				case "detail":
					$keyboards = [
						[
							"action" => "transactions",
							"text" => "📃 Show 10",
							"value" => $id,
						],
						[
							"action" => "help",
							"text" => "❓️ Bantuan",
							"value" => $id,
						],
					];

					return [
						"success" => true,
						"status" => "show_account",
						"edit_message" => [
							"text" => $this->getAccountDetail($account),
							"reply_markup" => $this->generateKeyboard($keyboards, $params),

							"parse_mode" => "MarkdownV2",
						],
					];
				case "create":
					return $this->createAccount($user, $params);

				case "transactions":
					return [
						"success" => true,
						"status" => "show_transactions",
						"edit_message" => [
							"text" => $this->getListTransactions($account, 10),
							"parse_mode" => "MarkdownV2",
						],
					];

				case "help":
				default:
					return [
						"success" => true,
						"status" => "show_help",
						"edit_message" => [
							"text" => $this->getAccountHelp(),
							"parse_mode" => "MarkdownV2",
						],
					];
			}
		} catch (\Exception $e) {
			throw $e;
		}
	}

	private function getAccountDetail(Account $account): string
	{
		$balance = Number::format($account->balance->getAmount()->toInt());
		$initial = Number::format($account->initial_balance->getAmount()->toInt());

		$message = "🏦 *Detail Akun*\n\n";
		$message .= "📛 *Nama:* {$account->name}\n";
		$message .= "💰 *Saldo:* Rp {$balance}\n";
		$message .= "📊 *Saldo Awal:* Rp {$initial}\n";
		$message .= "💳 *Tipe:* " . ucfirst($account->type->value) . "\n";

		if ($account->account_number) {
			$message .= "🔢 *No. Rekening:* {$account->account_number}\n";
		}

		if ($account->bank_name) {
			$message .= "🏛️ *Bank:* {$account->bank_name}\n";
		}

		// Monthly stats
		$monthlyStats = $this->getAccountMonthlyStats($account);
		$message .= "\n📈 *Statistik Bulan Ini:*\n";
		$message .= "💰 *Pemasukan:* Rp " . $monthlyStats["income"] . "\n";
		$message .= "💸 *Pengeluaran:* Rp " . $monthlyStats["expense"] . "\n";
		$message .= "📊 *Net:* Rp " . $monthlyStats["net"] . "\n";

		if ($account->notes) {
			$message .= "\n📝 *Catatan:* {$account->notes}\n";
		}

		return $message;
	}

	private function getAccountHelp(): string
	{
		$type = collect(TransactionType::cases())
			->map(fn($type) => "`" . $type->value . "`")
			->join(", ", " and ");

		return "📝 *Gunakan:*\n" .
			"`/add <tipe> <jumlah> <deskripsi> [#kategori] [@akun]`\n\n" .
			"📋 *Contoh:*\n" .
			"• `/add expense 50000 Makan siang #Food @Cash`\n" .
			"• `/add income 2000000 Gaji bulanan #Salary @Bank`\n" .
			"• `/add transfer 1000000 Tabungan #Transfer @Savings`\n\n" .
			"💡 *Keterangan:*\n" .
			"• Tipe: {$type}\n" .
			"• #kategori dan @akun bersifat opsional\n" .
			"• Gunakan tanpa spasi untuk nama multi-kata";
	}

	private function getAccountMonthlyStats(Account $account): array
	{
		$startOfMonth = Carbon::now()->startOfMonth();
		$endOfMonth = Carbon::now()->endOfMonth();

		$income = $account
			->transactions()
			->where("type", TransactionType::INCOME)
			->whereBetween("transaction_date", [$startOfMonth, $endOfMonth])
			->sum("amount");

		$expense = $account
			->transactions()
			->where("type", TransactionType::EXPENSE)
			->whereBetween("transaction_date", [$startOfMonth, $endOfMonth])
			->sum("amount");

		return [
			"income" => (int) Helper::toMoney($income)
				->getAmount()
				->toInt(),
			"expense" => (int) Helper::toMoney($expense)
				->getAmount()
				->toInt(),
			"net" => (int) Helper::toMoney($income - $expense)
				->getAmount()
				->toInt(),
		];
	}

	private function generateKeyboard(array $keyboards, array $params = [])
	{
		if (isset($params["scope"])) {
			$this->keyboard->setScope($params["scope"]);
		}

		if (isset($params["module"])) {
			$this->keyboard->setModule($params["module"]);
		}

		if (isset($params["entity"])) {
			$this->keyboard->setEntity($params["entity"]);
		}

		return [
			"inline_keyboard" => $this->keyboard->grid($keyboards, 2),
		];
	}

	private function createAccount(User $user, array $params): array
	{
		return [
			"success" => true,
			"status" => "request_account_name",
			"delete_message" => true,
			"send_message" => [
				"text" => "Input account name",
				"reply_markup" => ["force_reply" => true],
			],
			"reply_handler" => [
				"identifier" => $params["module"] . ":" . $params["entity"] . ":create",
			],
		];
	}

	private function getListTransactions(Account $account, int $limit): string
	{
		$transactions = $account
			->transactions()
			->orderByDesc("transaction_date")
			->limit($limit)
			->get();
		\Log::info("Total transactions: " . $transactions->count(), [
			"data" => $transactions,
		]);

		$messages = "*📃 Show {$limit} from {$transactions->count()}Transactions in account {$account->name}*\n\n";

		foreach ($transactions as $transaction) {
			$amount = $transaction->amount->getAmount()->toInt();

			$messages .=
				"● 🗓 {$transaction->transaction_date}\n" . "  💵 {$amount}\n";
			if ($transaction->type === TransactionType::EXPENSE) {
				$messages .= "  🏷 {$transaction->description} - {$transaction->type->label()}";
			} else {
				$messages .= "  💳 {$transaction->description} - {$transaction->type->label()}";
			}
			$messages .= "\n\n";
		}

		return $messages;
	}
}
