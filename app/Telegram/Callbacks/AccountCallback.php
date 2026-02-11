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
		int $accountId,
		array $params = []
	) {
		try {
			$account = $this->repo->find($accountId);
			$this->service->validateAccount($account, $user);

			switch ($action) {
				case "detail":
					$message = $this->getAccountDetail($account);
					$params = array_merge(
						[
							"action" => "help",
							"text" => "❓️ Bantuan",
							"value" => $accountId,
						],
						$params
					);
					$keyboard = $this->generateKeyboard($params);

					return ["message" => $message, "keyboard" => $keyboard];

				case "create":
					return $this->createAccount($user, $params);

				case "help":
				default:
					return ["message" => $this->getAccountHelp($account)];
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
		$message .=
			"💰 *Pemasukan:* Rp " . Number::format($monthlyStats["income"]) . "\n";
		$message .=
			"💸 *Pengeluaran:* Rp " . Number::format($monthlyStats["expense"]) . "\n";
		$message .= "📊 *Net:* Rp " . Number::format($monthlyStats["net"]) . "\n";

		if ($account->notes) {
			$message .= "\n📝 *Catatan:* {$account->notes}\n";
		}

		return $message;
	}

	private function getAccountHelp(Account $account): string
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
			"income" => (int) Helper::formatMoney($income),
			"expense" => (int) Helper::formatMoney($expense),
			"net" => (int) Helper::formatMoney($income - $expense),
		];
	}

	private function generateKeyboard(array $params = [])
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

		if (!isset($params["text"])) {
			$params["text"] = "";
		}
		if (!isset($params["value"])) {
			$params["value"] = "";
		}
		if (!isset($params["action"])) {
			$params["action"] = "list";
		}

		return [
			"inline_keyboard" => $this->keyboard->grid(
				[["text" => $params["text"], "value" => $params["value"]]],
				2,
				$params["action"]
			),
		];
	}

	private function createAccount(User $user, array $params): array
	{
		return ["message" => "Masukkan nama account"];
	}
}
