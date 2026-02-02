<?php
namespace Modules\Wallet\Services\Telegram\Handlers;

use App\Models\User;
use Telegram\Bot\Objects\CallbackQuery;
use Modules\Wallet\Models\Budget;
use Modules\Wallet\Services\Telegram\Support\TelegramApi;
use Modules\Wallet\Services\Telegram\Builders\MessageBuilder;
use Modules\Wallet\Services\Telegram\Builders\KeyboardBuilder;
use Illuminate\Support\Facades\Cache;

class BudgetCallbackHandler extends BaseCallbackHandler
{
	protected MessageBuilder $messageBuilder;
	protected KeyboardBuilder $keyboardBuilder;

	public function __construct(
		TelegramApi $telegramApi,
		MessageBuilder $messageBuilder,
		KeyboardBuilder $keyboardBuilder
	) {
		parent::__construct($telegramApi);
		$this->messageBuilder = $messageBuilder;
		$this->keyboardBuilder = $keyboardBuilder;
	}

	public function handle(
		User $user,
		array $data,
		CallbackQuery $callbackQuery
	): array {
		$this->setContext($user, $callbackQuery);

		$action = $data["action"] ?? null;
		$budgetId = $data["id"] ?? null;

		if (!$action || !$budgetId) {
			$this->answerCallbackQuery("❌ Data tidak valid", true);
			return ["success" => false, "message" => "Invalid data"];
		}

		$methodName = "handle" . ucfirst($action);
		if (!method_exists($this, $methodName)) {
			$this->answerCallbackQuery("❌ Aksi tidak didukung", true);
			return ["success" => false, "message" => "Unsupported action"];
		}

		return $this->$methodName($budgetId, $data);
	}

	public function supports(string $action, string $type): bool
	{
		return $type === "budget";
	}

	private function handleView(string $budgetId, array $data): array
	{
		$budget = $this->validateUserOwnership(Budget::class, $budgetId);
		if (!$budget) {
			return ["success" => false];
		}

		$message = $this->messageBuilder->buildBudgetDetailMessage($budget);
		$keyboard = $this->keyboardBuilder->buildBudgetDetailKeyboard($budget);

		$this->editMessageText($message, $keyboard);
		$this->answerCallbackQuery("📊 Memuat detail budget...");

		return ["success" => true, "budget_id" => $budgetId];
	}

	private function handleMute(string $budgetId, array $data): array
	{
		$budget = $this->validateUserOwnership(Budget::class, $budgetId);
		if (!$budget) {
			return ["success" => false];
		}

		$cacheKey = "telegram_muted:budget_{$budgetId}_{$this->user->id}";
		Cache::put($cacheKey, true, now()->addHours(24));

		$originalMessage = $this->callbackQuery->getMessage()->getText();
		$message =
			$originalMessage . "\n\n🔕 *Notifikasi dinonaktifkan sementara (24 jam)*";

		$keyboard = $this->keyboardBuilder->buildBudgetMutedKeyboard($budgetId);

		$this->editMessageText($message, $keyboard);
		$this->answerCallbackQuery("🔕 Notifikasi dinonaktifkan untuk 24 jam");

		return ["success" => true, "muted_until" => now()->addHours(24)];
	}

	private function handleRefresh(string $budgetId, array $data): array
	{
		return $this->handleView($budgetId, $data);
	}

	private function handleChart(string $budgetId, array $data): array
	{
		$budget = $this->validateUserOwnership(Budget::class, $budgetId);
		if (!$budget) {
			return ["success" => false];
		}

		// Get chart data from budget service
		$chartData = $this->getBudgetChartData($budget);

		$message = $this->messageBuilder->buildBudgetChartMessage(
			$budget,
			$chartData
		);
		$keyboard = $this->keyboardBuilder->buildBudgetChartKeyboard($budgetId);

		$this->editMessageText($message, $keyboard);
		$this->answerCallbackQuery("📈 Memuat grafik budget...");

		return ["success" => true];
	}

	private function handleDelete(string $budgetId, array $data): array
	{
		$message = "🗑️ *Konfirmasi Hapus Budget*\n\n";
		$message .= "Apakah Anda yakin ingin menghapus budget ini?\n";
		$message .= "Tindakan ini tidak dapat dibatalkan!";

		$keyboard = [
			"inline_keyboard" => [
				[
					[
						"text" => "✅ Ya, Hapus",
						"callback_data" => json_encode([
							"action" => "confirm_delete",
							"type" => "budget",
							"id" => $budgetId,
						]),
					],
					[
						"text" => "❌ Batal",
						"callback_data" => json_encode(["action" => "cancel"]),
					],
				],
			],
		];

		$this->editMessageText($message, $keyboard);
		$this->answerCallbackQuery("⚠️ Konfirmasi penghapusan budget");

		return ["success" => true];
	}

	private function handleConfirmDelete(string $budgetId, array $data): array
	{
		$budget = $this->validateUserOwnership(Budget::class, $budgetId);
		if (!$budget) {
			return ["success" => false];
		}

		try {
			$budgetName = $budget->name;
			$budget->delete();

			$message = "✅ *Budget berhasil dihapus*\n\n";
			$message .= "Budget '{$budgetName}' telah dihapus dari sistem.";

			$this->editMessageText($message);
			$this->answerCallbackQuery("✅ Budget dihapus");

			return ["success" => true, "deleted" => true];
		} catch (\Exception $e) {
			Log::error("Failed to delete budget", [
				"budget_id" => $budgetId,
				"error" => $e->getMessage(),
			]);

			$this->editMessageText(
				'❌ *Gagal menghapus budget*\n\nTerjadi kesalahan sistem.'
			);
			$this->answerCallbackQuery("❌ Gagal menghapus", true);

			return ["success" => false];
		}
	}

	private function getBudgetChartData(Budget $budget): array
	{
		// Implement chart data retrieval
		return [
			"labels" => ["Minggu 1", "Minggu 2", "Minggu 3", "Minggu 4"],
			"data" => [
				"spent" => [150000, 200000, 180000, 220000],
				"budget" => [200000, 200000, 200000, 200000],
			],
			"remaining" => $budget->remaining,
			"daily_average" => $budget->daily_budget,
		];
	}
}
