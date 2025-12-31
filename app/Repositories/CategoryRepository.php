<?php

namespace Modules\Wallet\Repositories;

use App\Models\User;
use Modules\Wallet\Models\Category;
use Illuminate\Support\Collection;

class CategoryRepository extends BaseRepository
{
	public function __construct(Category $model)
	{
		parent::__construct($model);
	}

	/**
	 * Create new category with budget limit
	 */
	public function createCategory(array $data, User $user): Category
	{
		// Convert budget limit to Money
		if (isset($data["budget_limit"])) {
			$data["budget_limit"] = $this->toDatabaseAmount(
				$this->toMoney($data["budget_limit"])
			);
		}

		$data["user_id"] = $user->id;

		return $this->create($data);
	}

	/**
	 * Update category with budget limit
	 */
	public function updateCategory(int $id, array $data): Category
	{
		if (isset($data["budget_limit"])) {
			$data["budget_limit"] = $this->toDatabaseAmount(
				$this->toMoney($data["budget_limit"])
			);
		}

		$this->update($id, $data);
		return $this->find($id);
	}

	/**
	 * Get categories by type
	 */
	public function getByType(string $type, User $user): Collection
	{
		return $this->model
			->where("user_id", $user->id)
			->where("type", $type)
			->where("is_active", true)
			->orderBy("name")
			->get();
	}

	/**
	 * Get categories with monthly totals
	 */
	public function getWithMonthlyTotals(
		User $user,
		int $month = null,
		int $year = null
	): Collection {
		$month = $month ?? date("m");
		$year = $year ?? date("Y");

		return $this->model
			->where("user_id", $user->id)
			->where("type", "expense")
			->with([
				"transactions" => function ($query) use ($month, $year) {
					$query
						->whereMonth("transaction_date", $month)
						->whereYear("transaction_date", $year)
						->where("type", "expense");
				},
			])
			->get()
			->map(function ($category) {
				$category->monthly_total = $category->transactions->sum("amount");
				$category->budget_usage = $category->budget_limit
					? ($category->monthly_total / $category->budget_limit) * 100
					: 0;
				return $category;
			});
	}
}
