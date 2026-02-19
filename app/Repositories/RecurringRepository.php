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

	public function getUpcomingTransactions(int $days = 30, User $user): array
	{
		$upcoming = [];
		$today = now();
		$endDate = $today->copy()->addDays($days);

		$recurringTransactions = RecurringTransaction::with(["account", "category"])
			->where("user_id", $user->id)
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
					"account" => $recurring->account ?? null,
					"account_name" => $recurring->account->name ?? "N/A",
					"category" => $recurring->category->name ?? null,
					"category_icon" => $recurring->category->icon ?? null,
					"days_until" => $nextDate->diffForHumans() ?? null,
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

	public function paginateWithFilters(
		array $filters = [],
		int $perPage = 10
	): array {
		$query = $this->model
			->with(["account", "category"])
			->where("user_id", auth()->id());

		// Apply filters
		if (!empty($filters["status"])) {
			if ($filters["status"] === "active") {
				$query->where("is_active", true);
			} elseif ($filters["status"] === "inactive") {
				$query->where("is_active", false);
			}
		}

		if (!empty($filters["type"])) {
			$query->where("type", $filters["type"]);
		}

		if (!empty($filters["frequency"])) {
			$query->where("frequency", $filters["frequency"]);
		}

		if (!empty($filters["search"])) {
			$query->where(function ($q) use ($filters) {
				$q->where("description", "like", "%" . $filters["search"] . "%")
					->orWhereHas("account", function ($q) use ($filters) {
						$q->where("name", "like", "%" . $filters["search"] . "%");
					})
					->orWhereHas("category", function ($q) use ($filters) {
						$q->where("name", "like", "%" . $filters["search"] . "%");
					});
			});
		}

		$transactions = $query->orderBy("created_at", "desc")->paginate($perPage);

		// Calculate stats
		$stats = [
			"total" => $this->model->where("user_id", auth()->id())->count(),
			"active" => $this->model
				->where("user_id", auth()->id())
				->where("is_active", true)
				->count(),
			"inactive" => $this->model
				->where("user_id", auth()->id())
				->where("is_active", false)
				->count(),
			"total_amount" => $this->model
				->where("user_id", auth()->id())
				->sum("amount"),
		];

		return [
			"transactions" => $transactions,
			"stats" => $stats,
		];
	}
}
