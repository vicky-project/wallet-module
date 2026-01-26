<?php

namespace Modules\Wallet\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Wallet\Models\Tag;

class TagController extends Controller
{
	/**
	 * Display a listing of tags.
	 */
	public function index(Request $request)
	{
		$user = $request->user();

		$tags = Tag::forUser($user->id)
			->withCount("transactions")
			->orderBy("name")
			->paginate(20);

		$stats = [
			"total_tags" => Tag::forUser($user->id)->count(),
			"most_used_tag" => Tag::forUser($user->id)
				->withCount("transactions")
				->orderBy("transactions_count", "desc")
				->first(),
			"new_tags_last_30_days" => Tag::forUser($user->id)
				->where("created_at", ">=", now()->subDays(30))
				->count(),
			"unused_tags" => Tag::forUser($user->id)
				->whereDoesntHave("transactions")
				->count(),
		];

		$recentlyUsedTags = Tag::forUser($user->id)
			->whereHas("transactions", function ($query) {
				$query->where("transaction_date", ">=", now()->subDays(30));
			})
			->withCount("transactions")
			->orderBy("created_at", "desc")
			->limit(15)
			->get();

		$popularTags = Tag::getPopularTags($user->id, 20);

		if ($request->wantsJson()) {
			return response()->json([
				"data" => $tags,
				"stats" => $stats,
			]);
		}

		return view(
			"tags.index",
			compact("tags", "stats", "recentlyUsedTags", "popularTags")
		);
	}

	/**
	 * Show the form for creating a new tag.
	 */
	public function create(): View
	{
		return view("tags.form");
	}

	/**
	 * Show the form for editing the specified tag.
	 */
	public function edit(Request $request, $id): View
	{
		$user = $request->user();

		$tag = Tag::forUser($user->id)
			->withCount("transactions")
			->findOrFail($id);

		$similarTags = $tag->getSimilarTags();

		return view("tags.form", compact("tag", "similarTags"));
	}

	/**
	 * Display the specified tag.
	 */
	public function show(Request $request, $id): View
	{
		$user = $request->user();

		$tag = Tag::forUser($user->id)
			->withCount("transactions")
			->findOrFail($id);

		$transactions = $tag
			->transactions()
			->with(["category", "account"])
			->latest("transaction_date")
			->paginate(20);

		// Monthly usage statistics
		$monthlyUsage = \DB::table("transactions")
			->join(
				"transaction_tags",
				"transactions.id",
				"=",
				"transaction_tags.transaction_id"
			)
			->where("transaction_tags.tag_id", $tag->id)
			->selectRaw(
				'MONTH(transaction_date) as month, 
                        YEAR(transaction_date) as year,
                        CONCAT(YEAR(transaction_date), "-", LPAD(MONTH(transaction_date), 2, "0")) as month_key,
                        CONCAT(MONTHNAME(transaction_date), " ", YEAR(transaction_date)) as month_label,
                        COUNT(*) as count,
                        SUM(amount) as total'
			)
			->groupBy("year", "month", "month_key", "month_label")
			->orderBy("year", "desc")
			->orderBy("month", "desc")
			->limit(6)
			->get();

		// Category distribution
		$categoryDistribution = \DB::table("transactions")
			->join(
				"transaction_tags",
				"transactions.id",
				"=",
				"transaction_tags.transaction_id"
			)
			->join("categories", "transactions.category_id", "=", "categories.id")
			->where("transaction_tags.tag_id", $tag->id)
			->selectRaw(
				'categories.id as category_id, 
                        categories.name as category_name,
                        COUNT(*) as transaction_count,
                        SUM(transactions.amount) as total'
			)
			->groupBy("categories.id", "categories.name")
			->orderBy("total", "desc")
			->limit(5)
			->get();

		return view(
			"tags.show",
			compact("tag", "transactions", "monthlyUsage", "categoryDistribution")
		);
	}

	/**
	 * Get tags for transaction form.
	 */
	public function getTagsForTransaction(Request $request)
	{
		$user = $request->user();

		$tags = Tag::forUser($user->id)
			->withCount("transactions")
			->orderBy("name")
			->get();

		if ($request->has("selected")) {
			$selectedTags = Tag::forUser($user->id)
				->whereIn("id", explode(",", $request->selected))
				->get();
		} else {
			$selectedTags = collect();
		}

		return view("components.tag-input", compact("tags", "selectedTags"));
	}
}
