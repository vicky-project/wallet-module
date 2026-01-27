<?php

namespace Modules\Wallet\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Routing\Controller;
use Modules\Wallet\Models\Tag;
use Modules\Wallet\Http\Requests\TagRequest;

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

		if ($request->wantsJson() || $request->json) {
			return response()->json([
				"data" => $tags,
				"stats" => $stats,
			]);
		}

		return view(
			"wallet::tags.index",
			compact("tags", "stats", "recentlyUsedTags", "popularTags")
		);
	}

	/**
	 * Show the form for creating a new tag.
	 */
	public function create(): View
	{
		return view("wallet::tags.form");
	}

	/**
	 * Store a new tag
	 */
	public function store(TagRequest $request)
	{
		$user = $request->user();

		$tag = Tag::create([
			"user_id" => $user->id,
			"name" => $request->name,
			"color" => $request->color,
			"icon" => $request->icon,
		]);

		// Clear cache
		cache()->forget("user_{$user->id}_tags");

		return redirect()
			->route("apps.tags.index")
			->with("success", "Tag berhasil dibuat");
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

		return view("wallet::tags.form", compact("tag", "similarTags"));
	}

	/**
	 * Update tag
	 */
	public function update(TagRequest $request, Tag $tag)
	{
		$user = $request->user();

		$tag->update([
			"name" => $request->name,
			"color" => $request->color,
			"icon" => $request->icon,
		]);

		// Clear cache
		cache()->forget("user_{$user->id}_tags");
		cache()->forget("tag_{$tag->id}_usage_count");

		return redirect()
			->route("apps.tags.index")
			->with("success", "Tag berhasil diperbarui");
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
			"wallet::tags.show",
			compact("tag", "transactions", "monthlyUsage", "categoryDistribution")
		);
	}

	/**
	 * Delete tag (soft delete)
	 */
	public function destroy(Request $request, Tag $tag)
	{
		$user = $request->user();

		// Check if tag is used in transactions
		if ($tag->transactions()->count() > 0) {
			return back()->withErrors(
				"Tidak dapat menghapus tag yang masih digunakan dalam transaksi"
			);
		}

		$tag->delete();

		// Clear cache
		cache()->forget("user_{$user->id}_tags");

		return redirect()
			->route("apps.tags.index")
			->with("success", "Tag berhasil dihapus");
	}

	/**
	 * Force delete tag
	 */
	public function forceDestroy(Request $request, $id)
	{
		$user = $request->user();

		$tag = Tag::forUser($user->id)
			->withTrashed()
			->findOrFail($id);

		// Remove all tag associations first
		$tag->transactions()->detach();

		$tag->forceDelete();

		// Clear cache
		cache()->forget("user_{$user->id}_tags");

		return back()->with("success", "Tag berhasil dihapus permanen");
	}

	/**
	 * Restore deleted tag
	 */
	public function restore(Request $request, $id)
	{
		$user = $request->user();

		$tag = Tag::forUser($user->id)
			->withTrashed()
			->findOrFail($id);

		$tag->restore();

		// Clear cache
		cache()->forget("user_{$user->id}_tags");

		return back()->with("success", "Tag berhasil dipulihkan");
	}

	/**
	 * Merge tags
	 */
	public function merge(Request $request): JsonResponse
	{
		$request->validate([
			"source_tag_id" => "required|exists:tags,id",
			"target_tag_id" => "required|exists:tags,id|different:source_tag_id",
		]);

		$user = $request->user();

		$sourceTag = Tag::forUser($user->id)->findOrFail($request->source_tag_id);
		$targetTag = Tag::forUser($user->id)->findOrFail($request->target_tag_id);

		if ($sourceTag->mergeInto($targetTag)) {
			// Clear cache
			cache()->forget("user_{$user->id}_tags");

			return redirect()
				->route("apps.tags.index")
				->with("success", "Tag berhasil digabungkan");
		}

		return back()->withErrors("Gagal menggabungkan tag");
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

		return view("wallet::partials.tag-input", compact("tags", "selectedTags"));
	}
}
