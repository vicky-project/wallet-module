<?php
namespace Modules\Wallet\Events;

use App\Models\User;
use Modules\Wallet\Models\Account;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Telegram\Interfaces\TelegramNotifiable;

class LowBalanceEvent implements TelegramNotifiable
{
	use Dispatchable, SerializesModels;

	public User $user;
	public ?Account $account;
	public bool $isSuccess;
	public array $options = [];

	public function __construct(
		User $user,
		?Account $account = null,
		bool $isSuccess = true,
		array $options = [],
	) {
		$this->user = $user;
		$this->account = $account;
		$this->isSuccess = $isSuccess;
		$this->options = $options;
	}

	public function getUser(): User
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
		if (!$this->account || !$this->isSuccess) {
			return $this->options["message"] ??
				"Gagal mengirimikan account notifikasi";
		}

		$accountName = $this->account->name;
		$balance = $this->account->balance->getAmount()->toInt();
		$threshold = $this->options["threshold"];

		return "Detected low balance on account {$accountName}\n\n" .
			"● 💰 {$balance}\n" .
			"● 〽️ {$threshold}";
	}
}
