<?php
namespace Modules\Wallet\Services\Telegram;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TelegramLinkService
{
	/**
	 * Generate and store linking code
	 */
	public function generateLinkingCode(User $user): array
	{
		$code = $user->generateTelegramVerificationCode();

		// Store in cache for quick validation
		Cache::put(
			"telegram_link:{$code}",
			[
				"user_id" => $user->id,
				"email" => $user->email,
				"name" => $user->name,
				"expires_at" => Carbon::now()->addMinutes(10),
			],
			600
		); // 10 minutes

		return [
			"code" => $code,
			"expires_at" => Carbon::parse($user->fresh()->telegram_code_expires_at),
			"bot_username" => config("telegram_bot.username", "your_bot_username"),
		];
	}

	/**
	 * Validate linking code
	 */
	public function validateLinkingCode(string $code): ?User
	{
		$cached = Cache::get("telegram_link:{$code}");

		if (!$cached) {
			return null;
		}

		$user = User::find($cached["user_id"]);

		if (!$user || !$user->verifyTelegramCode($code)) {
			Cache::forget("telegram_link:{$code}");
			return null;
		}

		return $user;
	}

	/**
	 * Complete linking process
	 */
	public function completeLinking(
		User $user,
		int $chatId,
		string $username = null
	): bool {
		try {
			$linked = $user->linkTelegramAccount($chatId, $username);

			if ($linked) {
				// Clear cache
				Cache::forget("telegram_link:{$user->telegram_verification_code}");

				// Log the linking
				Log::info("Telegram account linked", [
					"user_id" => $user->id,
					"chat_id" => $chatId,
					"username" => $username,
				]);
			}

			return $linked;
		} catch (\Exception $e) {
			Log::error("Failed to link Telegram account", [
				"user_id" => $user->id,
				"error" => $e->getMessage(),
			]);
			return false;
		}
	}

	/**
	 * Get user by Telegram chat ID
	 */
	public function getUserByChatId(int $chatId): ?User
	{
		return User::where("telegram_chat_id", $chatId)->first();
	}
}
