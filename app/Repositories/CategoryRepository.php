<?php

namespace Modules\Wallet\Repositories;

use Modules\Wallet\Models\Category;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CategoryRepository
{
	protected $category;

	public function __construct(Category $category)
	{
		$this->category = $category;
	}

	public function getUserCategories(
		?string $type = null,
		bool $includeInactive = false
	) {
		$query = $this->category->byUser(Auth::id());

		if ($type) {
			$query->where("type", $type);
		}

		if (!$includeInactive) {
			$query->active();
		}

		return $query
			->orderBy("type")
			->orderBy("order")
			->get();
	}

	public function createCategory(array $data)
	{
		$data["user_id"] = Auth::id();

		// Set order if not provided
		if (!isset($data["order"])) {
			$maxOrder = $this->category
				->where("user_id", Auth::id())
				->where("type", $data["type"])
				->max("order");
			$data["order"] = ($maxOrder ?? 0) + 1;
		}

		return $this->category->create($data);
	}

	public function updateCategory(Category $category, array $data)
	{
		$category->update($data);
		return $category;
	}

	public function deleteCategory(Category $category)
	{
		// Check if category is used in transactions
		if ($category->transactions()->count() > 0) {
			throw new \Exception(
				"Cannot delete category that is used in transactions"
			);
		}

		return $category->delete();
	}

	public function reorderCategories(array $categories)
	{
		DB::transaction(function () use ($categories) {
			foreach ($categories as $item) {
				$this->category
					->where("id", $item["id"])
					->update(["order" => $item["order"]]);
			}
		});
	}

	public function getCategoryUsage(Category $category)
	{
		return [
			"category" => $category,
			"transaction_count" => $category->transactions()->count(),
			"total_amount" => $category
				->transactions()
				->where("status", "completed")
				->sum("net_amount"),
			"monthly_usage" => $category
				->transactions()
				->where("status", "completed")
				->whereMonth("transaction_date", now()->month)
				->selectRaw(
					'
                    COUNT(*) as count,
                    SUM(net_amount) as total_amount,
                    AVG(net_amount) as average_amount
                '
				)
				->first(),
		];
	}

	public function getAllCategoriesUsage(
		?string $startDate = null,
		?string $endDate = null
	) {
		$query = Category::byUser(Auth::id())
			->withCount([
				"transactions as transaction_count" => function ($query) use (
					$startDate,
					$endDate
				) {
					$query->where("status", "completed");
					if ($startDate && $endDate) {
						$query->whereBetween("transaction_date", [$startDate, $endDate]);
					}
				},
			])
			->withSum(
				[
					"transactions as total_amount" => function ($query) use (
						$startDate,
						$endDate
					) {
						$query->where("status", "completed");
						if ($startDate && $endDate) {
							$query->whereBetween("transaction_date", [$startDate, $endDate]);
						}
					},
				],
				"net_amount"
			);

		return $query->get()->map(function ($category) {
			return [
				"id" => $category->id,
				"name" => $category->name,
				"type" => $category->type,
				"transaction_count" => $category->transaction_count,
				"total_amount" => $category->total_amount,
			];
		});
	}
}
