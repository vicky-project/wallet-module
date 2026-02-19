<?php

namespace Modules\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
	use SoftDeletes;

	protected $fillable = ["user_id", "name", "color", "icon"];

	protected $casts = [
		"color" => "string",
	];

	protected $appends = ["usage_count", "formatted_color"];

	/**
	 * Boot the model
	 */
	protected static function booted()
	{
		static::creating(function ($tag) {
			// Generate random color if not provided
			if (empty($tag->color) || $tag->color === "#6c757d") {
				$tag->color = self::generateRandomColor();
			}
		});

		static::deleted(function ($tag) {
			// Clear cache when tag is deleted
			cache()->forget("user_{$tag->user_id}_tags");
		});
	}

	/**
	 * Relationship with user
	 */
	public function user(): BelongsTo
	{
		return $this->belongsTo(config("auth.providers.users.model"));
	}

	/**
	 * Relationship with transactions
	 */
	public function transactions(): BelongsToMany
	{
		return $this->belongsToMany(
			Transaction::class,
			"transaction_tags",
			"tag_id",
			"transaction_id"
		)->withTimestamps();
	}

	/**
	 * Scope for current user
	 */
	public function scopeForUser($query, $userId)
	{
		return $query->where("user_id", $userId);
	}

	/**
	 * Scope for active tags (non-deleted)
	 */
	public function scopeActive($query)
	{
		return $query->whereNull("deleted_at");
	}

	/**
	 * Scope for search
	 */
	public function scopeSearch($query, $search)
	{
		return $query->where("name", "like", "%{$search}%");
	}

	/**
	 * Get usage count attribute
	 */
	public function getUsageCountAttribute(): int
	{
		// Using cached count for performance
		return cache()->remember(
			"tag_{$this->id}_usage_count",
			now()->addHours(6),
			fn() => $this->transactions()->count()
		);
	}

	/**
	 * Get formatted color attribute (with fallback)
	 */
	public function getFormattedColorAttribute(): string
	{
		if (!$this->color || !preg_match('/^#[0-9A-F]{6}$/i', $this->color)) {
			return "#6c757d";
		}
		return $this->color;
	}

	/**
	 * Get light version of color
	 */
	public function getLightColorAttribute(): string
	{
		$color = ltrim($this->formatted_color, "#");

		if (strlen($color) == 6) {
			list($r, $g, $b) = sscanf($color, "%02x%02x%02x");

			// Lighten color by 80%
			$r = min(255, $r + (255 - $r) * 0.8);
			$g = min(255, $g + (255 - $g) * 0.8);
			$b = min(255, $b + (255 - $b) * 0.8);

			return sprintf("#%02x%02x%02x", $r, $g, $b);
		}

		return "#f8f9fa";
	}

	/**
	 * Generate random color
	 */
	public static function generateRandomColor(): string
	{
		$colors = [
			"#0d6efd", // Blue
			"#6f42c1", // Purple
			"#20c997", // Teal
			"#fd7e14", // Orange
			"#dc3545", // Red
			"#198754", // Green
			"#6c757d", // Gray
			"#0dcaf0", // Cyan
			"#ffc107", // Yellow
			"#6610f2", // Indigo
		];

		return $colors[array_rand($colors)];
	}

	/**
	 * Get suggestions for similar tags
	 */
	public function getSimilarTags($limit = 5)
	{
		return self::forUser($this->user_id)
			->where("id", "!=", $this->id)
			->where(function ($query) {
				$query
					->where("name", "like", "%{$this->name}%")
					->orWhere("color", $this->color);
			})
			->limit($limit)
			->get();
	}

	/**
	 * Merge this tag into another tag
	 */
	public function mergeInto(Tag $targetTag): bool
	{
		if ($this->user_id !== $targetTag->user_id) {
			return false;
		}

		\DB::transaction(function () use ($targetTag) {
			// Update all transaction_tags to use target tag
			\DB::table("transaction_tags")
				->where("tag_id", $this->id)
				->update(["tag_id" => $targetTag->id]);

			// Delete this tag
			$this->delete();
		});

		return true;
	}

	/**
	 * Get popular tags for user
	 */
	public static function getPopularTags($userId, $limit = 10)
	{
		return self::forUser($userId)
			->withCount("transactions")
			->orderBy("transactions_count", "desc")
			->limit($limit)
			->get();
	}

	/**
	 * Get tags with usage statistics
	 */
	public static function getTagsWithStats(
		$userId,
		$period = "month",
		$categoryId = null
	) {
		$query = self::forUser($userId)
			->withCount(["transactions as total_transactions"])
			->withSum([
				"transactions as total_amount" => function ($query) use (
					$period,
					$categoryId
				) {
					if ($period === "month") {
						$query
							->whereMonth("transaction_date", now()->month)
							->whereYear("transaction_date", now()->year);
					} elseif ($period === "week") {
						$query->whereBetween("transaction_date", [
							now()->startOfWeek(),
							now()->endOfWeek(),
						]);
					}

					if ($categoryId) {
						$query->where("category_id", $categoryId);
					}
				},
			])
			->orderBy("total_amount", "desc");

		return $query->get();
	}
}
