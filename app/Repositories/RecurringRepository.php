<?php
namespace Modules\Wallet\Repositories;

use App\Models\User;
use Carbon\Carbon;
use Modules\Wallet\Models\RecurringTransaction;

class RecurringRepository extends BaseRepository
{
	public function __construct(RecurringTransaction $model)
	{
		parent::__construct($model);
	}

	public function getDashboardData(User $user, Carbon $now)
	{
		return ["upcoming" => $this->getUpcomingTransactions(7, $user)];
	}

	public function getUpcomingTransactions(int $days = 30): array
	{
		$upcoming = [];
		$today = now();
		$endDate = $today->copy()->addDays($days);

		$recurringTransactions = RecurringTransaction::with(["account", "category"])
			->where("is_active", true)
			->where("start_date", "<=", $endDate)
			->where(function ($query) use ($today) {
				$query->whereNull("end_date")->orWhere("end_date", ">=", $today);
			})
			->get();

		foreach ($recurringTransactions as $recurring) {
			$nextDate = $recurring->getNextDueDate();
			if ($nextDate && $nextDate->between($today, $endDate)) {
				$upcoming[] = [
					"recurring" => $recurring,
					"next_date" => $nextDate->format("Y-m-d"),
					"amount" => $recurring->amount,
					"description" => $recurring->description,
					"frequency" => $recurring->getFrequencyLabel(),
					"account" => $recurring->account->name ?? "N/A",
					"category" => $recurring->category->name ?? "N/A",
					"days_until" => $today->diffInDays($nextDate),
					"is_today" => $nextDate->isToday(),
				];
			}
		}

		// Sort by date
		usort($upcoming, function ($a, $b) {
			return strtotime($a["next_date"]) <=> strtotime($b["next_date"]);
		});

		return array_slice($upcoming, 0, 10);
	}
}
