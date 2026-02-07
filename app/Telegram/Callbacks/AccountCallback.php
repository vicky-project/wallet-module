<?php
namespace Modules\Wallet\Telegram\Callbacks;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Number;
use Modules\Wallet\Models\Account;
use Modules\Wallet\Enums\TransactionType;
use Modules\Wallet\Services\AccountService;

class AccountCallback
{
	protected AccountService $service;
	protected $repo;

	public function __construct(AccountService $service)
	{
		$this->service = $service;
		$this->repo = $this->service->getRepository();
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
					return $this->getAccountDetail($account);
					break;
				case "help":
				default:
					return $this->getAccountHelp($account);
					break;
			}
		} catch (\Exception $e) {
			throw $e;
		}
	}

	private function getAccountDetail(Account $account)
	{
		$balance = Number::format($account->balance->getAmount()->toInt());
		$initial = Number::format($account->initial_balance);

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

	private function getAccountHelp(Account $account)
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
			"income" => $income,
			"expense" => $expense,
			"net" => $income - $expense,
		];
	}
}
