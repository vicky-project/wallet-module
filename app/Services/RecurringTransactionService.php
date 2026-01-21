<?php

namespace Modules\Wallet\Services;

use App\Models\User;
use Modules\Wallet\Models\RecurringTransaction;
use Modules\Wallet\Repositories\RecurringRepository;
use Modules\Wallet\Enums\RecurringFreq;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RecurringTransactionService
{
	public function __construct(protected RecurringRepository $repository)
	{
	}

	public function getDashboardData(User $user, Carbon $now): array
	{
		try {
			$data = $this->recurringRepository->getDashboardData($user, $now);
			dd($data);

			return [
				"upcoming" => $data["upcoming"] ?? [],
			];
		} catch (\Exception $e) {
			dd($e);
			return ["upcoming" => []];
		}
	}

	public function processDueRecurringTransactions(): array
	{
		$results = [
			"processed" => 0,
			"skipped" => 0,
			"errors" => [],
			"details" => [],
		];

		// Get all active recurring transactions
		$recurringTransactions = RecurringTransaction::where("is_active", true)
			->where(function ($query) {
				$query->whereNull("end_date")->orWhere("end_date", ">=", now());
			})
			->where(function ($query) {
				$query
					->whereNull("remaining_occurrences")
					->orWhere("remaining_occurrences", ">", 0);
			})
			->get();

		foreach ($recurringTransactions as $recurring) {
			try {
				if ($recurring->shouldProcessToday()) {
					DB::transaction(function () use ($recurring, &$results) {
						$transaction = $recurring->process();

						$results["details"][] = [
							"recurring_id" => $recurring->id,
							"transaction_id" => $transaction->id,
							"amount" => $transaction->amount,
							"description" => $transaction->description,
							"date" => now()->format("Y-m-d"),
						];

						$results["processed"]++;
					});
				} else {
					$results["skipped"]++;
				}
			} catch (\Exception $e) {
				$results["errors"][] = [
					"recurring_id" => $recurring->id,
					"error" => $e->getMessage(),
					"trace" => $e->getTraceAsString(),
				];
			}
		}

		return $results;
	}

	public function getPaginatedRecurringTransactions(
		array $filters = [],
		int $perPage = 10
	) {
		return $this->repository->paginateWithFilters($filters, $perPage);
	}

	public function createRecurringTransaction(array $data): RecurringTransaction
	{
		$validated = $this->validateRecurringData($data);

		// Calculate remaining occurrences if end_date is provided
		if (
			isset($validated["end_date"]) &&
			!isset($validated["remaining_occurrences"])
		) {
			$validated[
				"remaining_occurrences"
			] = $this->calculateRemainingOccurrences(
				Carbon::parse($validated["start_date"]),
				Carbon::parse($validated["end_date"]),
				$validated["frequency"],
				$validated["interval"]
			);
		}

		return RecurringTransaction::create($validated);
	}

	public function updateRecurringTransaction(
		RecurringTransaction $recurring,
		array $data
	): bool {
		$validated = $this->validateRecurringData($data, $recurring);
		return $recurring->update($validated);
	}

	private function validateRecurringData(
		array $data,
		?RecurringTransaction $recurring = null
	): array {
		$validated = [
			"account_id" => $data["account_id"] ?? $recurring?->account_id,
			"category_id" => $data["category_id"] ?? $recurring?->category_id,
			"type" => $data["type"] ?? $recurring?->type,
			"amount" => $data["amount"] ?? $recurring?->amount,
			"description" => $data["description"] ?? $recurring?->description,
			"frequency" => $data["frequency"] ?? $recurring?->frequency,
			"interval" => $data["interval"] ?? ($recurring?->interval ?? 1),
			"start_date" => $data["start_date"] ?? ($recurring?->start_date ?? now()),
			"end_date" => $data["end_date"] ?? $recurring?->end_date,
			"is_active" => $data["is_active"] ?? ($recurring?->is_active ?? true),
			"remaining_occurrences" =>
				$data["remaining_occurrences"] ?? $recurring?->remaining_occurrences,
		];

		// Frequency-specific fields
		switch ($validated["frequency"]) {
			case RecurringFreq::WEEKLY:
				$validated["day_of_week"] =
					$data["day_of_week"] ??
					($recurring?->day_of_week ??
						Carbon::parse($validated["start_date"])->dayOfWeek);
				$validated["day_of_month"] = null;
				$validated["custom_schedule"] = null;
				break;

			case RecurringFreq::MONTHLY:
			case RecurringFreq::QUARTERLY:
				$validated["day_of_month"] =
					$data["day_of_month"] ??
					($recurring?->day_of_month ??
						Carbon::parse($validated["start_date"])->day);
				$validated["day_of_week"] = null;
				$validated["custom_schedule"] = null;
				break;

			case RecurringFreq::CUSTOM:
				$validated["custom_schedule"] =
					$data["custom_schedule"] ?? $recurring?->custom_schedule;
				$validated["day_of_month"] = null;
				$validated["day_of_week"] = null;
				if (empty($validated["custom_schedule"])) {
					throw new \InvalidArgumentException(
						"Custom schedule is required for custom frequency"
					);
				}
				break;

			default:
				// DAILY, YEARLY
				$validated["day_of_month"] = null;
				$validated["day_of_week"] = null;
				$validated["custom_schedule"] = null;
				break;
		}

		return $validated;
	}

	private function calculateRemainingOccurrences(
		Carbon $startDate,
		Carbon $endDate,
		RecurringFreq $frequency,
		int $interval
	): int {
		return match ($frequency) {
			RecurringFreq::DAILY => floor(
				$startDate->diffInDays($endDate) / $interval
			) + 1,
			RecurringFreq::WEEKLY => floor(
				$startDate->diffInWeeks($endDate) / $interval
			) + 1,
			RecurringFreq::MONTHLY => floor(
				$startDate->diffInMonths($endDate) / $interval
			) + 1,
			RecurringFreq::QUARTERLY => floor(
				$startDate->diffInQuarters($endDate) / $interval
			) + 1,
			RecurringFreq::YEARLY => floor(
				$startDate->diffInYears($endDate) / $interval
			) + 1,
			default => 0,
		};
	}

	public function findRecurringTransaction(int $id, array $with = [])
	{
		return $this->repository->find($id, $with);
	}

	public function toggleStatus(
		RecurringTransaction $recurring
	): RecurringTransaction {
		$recurring->is_active = !$recurring->is_active;
		$recurring->save();

		return $recurring->refresh();
	}
}
