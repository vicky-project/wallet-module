<?php
namespace Modules\Wallet\Services\Telegram\Builders;

use Modules\Wallet\Enums\TransactionType;
use Modules\Wallet\Models\Budget;
use Modules\Wallet\Models\Account;
use Modules\Wallet\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Number;

class MessageBuilder
{
	public function buildBudgetDetailMessage(Budget $budget): string
	{
		$spent = Number::format($budget->spent->getAmount()->toInt());
		$amount = Number::format($budget->amount->getAmount()->toInt());
		$remaining = Number::format($budget->remaining);
		$usage = round($budget->usage_percentage);

		$message = "📊 *Detail Budget*\n\n";
		$message .= "📋 *Nama:* {$budget->name}\n";
		$message .= "📂 *Kategori:* {$budget->category->name}\n";
		$message .= "💰 *Budget:* Rp {$amount}\n";
		$message .= "💸 *Terpakai:* Rp {$spent}\n";
		$message .= "📈 *Sisa:* Rp {$remaining}\n";
		$message .= "📊 *Penggunaan:* {$usage}%\n";
		$message .=
			"📅 *Periode:* " .
			$budget->start_date->format("d/m/Y") .
			" - " .
			$budget->end_date->format("d/m/Y") .
			"\n";
		$message .= "⏳ *Hari Tersisa:* {$budget->days_left}\n";
		$message .=
			"📆 *Budget Harian:* Rp " . Number::format($budget->daily_budget);

		return $message;
	}

	public function buildAccountDetailMessage(Account $account): string
	{
		$balance = Number::format($account->balance->getAmount()->toInt());
		$initial = Number::format($account->initial_balance);

		$message = "🏦 *Detail Akun*\n\n";
		$message .= "📛 *Nama:* {$account->name}\n";
		$message .= "💰 *Saldo:* Rp {$balance}\n";
		$message .= "📊 *Saldo Awal:* Rp {$initial}\n";
		$message .= "💳 *Tipe:* " . ucfirst($account->type) . "\n";

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

	public function buildTransactionDetailMessage(
		Transaction $transaction
	): string {
		$amount = Number::format($transaction->amount->getAmount()->toInt());
		$date = $transaction->transaction_date->format("d/m/Y H:i");
		$typeEmoji = $this->getTransactionTypeEmoji($transaction->type);

		$message = "{$typeEmoji} *Detail Transaksi*\n\n";
		$message .= "📝 *Deskripsi:* {$transaction->description}\n";
		$message .= "💰 *Jumlah:* Rp {$amount}\n";
		$message .= "📂 *Kategori:* {$transaction->category->name}\n";
		$message .= "🏦 *Akun:* {$transaction->account->name}\n";

		if (
			$transaction->type === TransactionType::TRANSFER &&
			$transaction->toAccount
		) {
			$message .= "➡️ *Ke Akun:* {$transaction->toAccount->name}\n";
		}

		$message .= "📅 *Tanggal:* {$date}\n";

		if ($transaction->payment_method) {
			$message .= "💳 *Metode Bayar:* {$transaction->payment_method->label()}\n";
		}

		if ($transaction->reference_number) {
			$message .= "🔢 *No. Referensi:* {$transaction->reference_number}\n";
		}

		if ($transaction->notes) {
			$message .= "\n📝 *Catatan:* {$transaction->notes}\n";
		}

		return $message;
	}

	public function buildAddFundsMessage(Account $account): string
	{
		$balance = Number::format($account->balance->getAmount()->toInt());

		$message = "💰 *Tambah Dana ke Akun*\n\n";
		$message .= "🏦 *Akun:* {$account->name}\n";
		$message .= "💳 *Saldo Saat Ini:* Rp {$balance}\n\n";
		$message .= "Silakan pilih jumlah yang ingin ditambahkan:";

		return $message;
	}

	public function buildFundsAddedMessage(Account $account, int $amount): string
	{
		$newBalance = Number::format($account->balance->getAmount()->toInt());
		$addedAmount = Number::format($amount);

		$message = "✅ *Dana berhasil ditambahkan*\n\n";
		$message .= "🏦 *Akun:* {$account->name}\n";
		$message .= "💰 *Jumlah:* Rp {$addedAmount}\n";
		$message .= "📈 *Saldo Baru:* Rp {$newBalance}\n";
		$message .= "📅 *Waktu:* " . now()->format("d/m/Y H:i:s");

		return $message;
	}

	private function getTransactionTypeEmoji(string $type): string
	{
		return match ($type) {
			TransactionType::INCOME => "💰",
			TransactionType::EXPENSE => "💸",
			TransactionType::TRANSFER => "🔄",
			default => "📝",
		};
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

	public function buildTransactionDeleteConfirmMessage(
		Transaction $transaction
	): string {
		$amount = Number::format($transaction->amount->getAmount()->toInt());
		$type =
			$transaction->type === TransactionType::INCOME
				? "Pemasukan"
				: "Pengeluaran";

		$message = "⚠️ *Konfirmasi Penghapusan*\n\n";
		$message .= "Anda yakin ingin menghapus transaksi ini?\n\n";
		$message .= "**Deskripsi:** {$transaction->description}\n";
		$message .= "**Jumlah:** Rp {$amount}\n";
		$message .= "**Tipe:** {$type}\n";
		$message .=
			"**Tanggal:** " . $transaction->transaction_date->format("d/m/Y") . "\n";
		$message .= "**Akun:** {$transaction->account->name}\n\n";
		$message .= "Tindakan ini tidak dapat dibatalkan!";

		return $message;
	}

	public function buildAddCommandUsage(): string
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
}
