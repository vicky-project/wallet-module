<?php
namespace Modules\Wallet\Services\Telegram\Types;

use App\Models\User;
use Modules\Wallet\Interfaces\NotificationInterface;
use Modules\Wallet\Services\Telegram\Support\TelegramApi;
use Modules\Wallet\Services\Telegram\Support\RateLimiter;
use Illuminate\Support\Facades\Log;

abstract class BaseNotification implements NotificationInterface
{
	protected TelegramApi $telegramApi;
	protected RateLimiter $rateLimiter;
	protected User $user;
	protected array $data;
	protected array $options;

	public function __construct(
		TelegramApi $telegramApi,
		RateLimiter $rateLimiter
	) {
		$this->telegramApi = $telegramApi;
		$this->rateLimiter = $rateLimiter;
	}

	public function setContext(
		User $user,
		array $data = [],
		array $options = []
	): self {
		$this->user = $user;
		$this->data = $data;
		$this->options = $options;
		return $this;
	}

	public function send(User $user): bool
	{
		if (!$this->shouldSend($user)) {
			return false;
		}

		// Check rate limit
		if ($this->rateLimiter->isRateLimited($user->id, $this->getType())) {
			Log::warning("Rate limited for notification", [
				"user_id" => $user->id,
				"type" => $this->getType(),
			]);
			return false;
		}

		try {
			$message = $this->buildMessage();
			$keyboard = $this->buildKeyboard();

			$result = $this->telegramApi->sendMessage(
				$user->telegram_chat_id,
				$message,
				"Markdown",
				$keyboard,
				$this->options
			);

			if ($result) {
				$this->rateLimiter->increment($user->id, $this->getType());
				Log::info("Telegram notification sent", [
					"user_id" => $user->id,
					"type" => $this->getType(),
				]);
			}

			return $result;
		} catch (\Exception $e) {
			Log::error("Failed to send Telegram notification", [
				"user_id" => $user->id,
				"type" => $this->getType(),
				"error" => $e->getMessage(),
			]);
			return false;
		}
	}

	public function shouldSend(User $user): bool
	{
		if (!$user->hasLinkedTelegram() || !$user->telegram_notifications) {
			return false;
		}

		// Check notification settings
		$settings = $user->getTelegramSettings();
		$notificationType = $this->getType();

		return $settings[$notificationType] ?? true;
	}

	abstract public function getType(): string;
	abstract public function buildMessage(): string;

	public function buildKeyboard(): ?array
	{
		return null;
	}
}
