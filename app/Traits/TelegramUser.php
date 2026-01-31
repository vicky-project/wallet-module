<?php
namespace Modules\Wallet\Traits;

use Illuminate\Support\Str;
use Carbon\Carbon;

trait TelegramUser
{
	protected $newFillable = [
		"telegram_chat_id",
		"telegram_username",
		"telegram_verification_code",
		"telegram_code_expires_at",
	];

	/**
	 * Generate verification code for Telegram linking
	 */
	public function generateTelegramVerificationCode(): string
	{
		$code = strtoupper(Str::random(6));
		$this->mergeFillable($this->newFillable)->update([
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
		return $this->mergeFillable($this->newFillable)->update([
			"telegram_chat_id" => $chatId,
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
		return $this->mergeFillable($this->newFillable)->update([
			"telegram_chat_id" => null,
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
		return !is_null($this->telegram_chat_id);
	}

	/**
	 * Get Telegram notification settings
	 */
	public function getTelegramSetting(string $key, $default = null)
	{
		$settings = $this->telegram_settings ?? [];
		return $settings[$key] ?? $default;
	}

	/**
	 * Update Telegram settings
	 */
	public function updateTelegramSettings(array $settings): bool
	{
		$current = $this->telegram_settings ?? [];
		return $this->mergeFillable($this->newFillable)->update([
			"telegram_settings" => array_merge($current, $settings),
		]);
	}
}
