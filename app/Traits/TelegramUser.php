<?php
namespace Modules\Wallet\Traits;

use Illuminate\Support\Str;
use Carbon\Carbon;

trait TelegramUser
{
	protected function prepare()
	{
		$fields = config("wallet.telegram_bot.table_fields");

		$this->mergeFillable(array_keys($fields))->mergeCasts($fields);
		return $this;
	}

	/**
	 * Generate verification code for Telegram linking
	 */
	public function generateTelegramVerificationCode(): string
	{
		$code = strtoupper(Str::random(6));
		$this->prepare()->update([
			"telegram_verification_code" => $code,
			"telegram_code_expires_at" => Carbon::now()->addMinutes(10),
		]);

		return $code;
	}

	/**
	 * Link Telegram account
	 */
	public function linkTelegramAccount(
		int $chatId,
		string $username = null
	): bool {
		return $this->prepare()->update([
			"telegram_id" => $chatId,
			"telegram_username" => $username,
			"telegram_verification_code" => null,
			"telegram_code_expires_at" => null,
		]);
	}

	/**
	 * Unlink Telegram account
	 */
	public function unlinkTelegramAccount(): bool
	{
		return $this->prepare()->update([
			"telegram_id" => null,
			"telegram_username" => null,
			"telegram_verification_code" => null,
			"telegram_code_expires_at" => null,
		]);
	}

	/**
	 * Verify Telegram linking code
	 */
	public function verifyTelegramCode(string $code): bool
	{
		if (
			!$this->telegram_verification_code ||
			!$this->telegram_code_expires_at ||
			$this->telegram_verification_code !== $code ||
			Carbon::now()->gt($this->telegram_code_expires_at)
		) {
			return false;
		}

		return true;
	}

	/**
	 * Check if user has linked Telegram
	 */
	public function hasLinkedTelegram(): bool
	{
		return !is_null($this->telegram_id);
	}

	/**
	 * Get Telegram notification settings
	 */
	public function getTelegramSetting(string $key, $default = null)
	{
		$settings = $this->telegram_settings ?? [];

		if (!isset($settings[$key])) {
			return $default;
		}

		return $settings[$key] ?? $default;
	}

	public function getAllTelegramSettings()
	{
		return $this->telegram_settings ?? [];
	}

	public function setTelegramNotification(bool $active)
	{
		$this->prepare()->update([
			"telegram_notifications" => $active,
		]);
	}

	/**
	 * Update Telegram settings
	 */
	public function updateTelegramSettings(array $settings): bool
	{
		$current = $this->getAllTelegramSettings();

		return $this->prepare()->update([
			"telegram_settings" => array_merge($current, $settings),
		]);
	}
}
