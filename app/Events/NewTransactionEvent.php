<?php
namespace Modules\Wallet\Events;

use App\Models\User;
use Modules\Wallet\Models\Transaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Telegram\Interfaces\TelegramNotifiable;

class NewTransactionEvent implements TelegramNotifiable
{
	use Dispatchable, SerializesModels;

	public User $user;
	public ?Transaction $transaction;
	public bool $isSuccess;
	public array $options = [];

	public function __construct(
		User $user,
		?Transaction $transaction = null,
		bool $isSuccess = true,
		array $options = []
	) {
		$this->user = $user;
		$this->message = $message;
		$this->isSuccess = $isSuccess;
		$this->options = $options;
	}

	public function getUser()
	{
		return $this->user;
	}

	public function getTelegramMessage(): string
	{
		$icon = $this->getIcon();
		$tile = $this->getTitle();
		$message = $this->buildMessage();

		return sprintf("%s *%s*\n\n%s", $icon, $title, $message);
	}

	public function getTelegramOptions(): array
	{
		return $this->options;
	}

	private function getIcon(): string
	{
		return $this->isSuccess ? "📢" : "⚠️";
	}

	private function getTitle(): string
	{
		return $this->isSuccess ? "Berhasil" : "Gagal";
	}

	private function buildMessage(): string
	{
		if (!$this->transaction || !$this->isSuccess) {
			return $this->options["message"] ?? "Gagal membuat transaksi baru";
		}

		$amount = $this->transaction->amount->getAmount()->toInt();
		$description = $this->transaction->description;
		$categoryName = $this->transaction->category->name;
		$accountName = $this->transaction->account->name;

		return "✅️ Transaksi baru berhasil di tambahkan.\n\n" .
			"● 💰 {$amount}\n" .
			"● 📃 {$description}\n" .
			"● 📫 {$categoryName}\n" .
			"● 🏦 {$accountName}";
	}
}
