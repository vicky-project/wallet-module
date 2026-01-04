<?php
namespace Modules\Wallet\Helpers;

use Modules\Wallet\Enums\CategoryType;

class Helper
{
	public static function listCurrencies()
	{
		return collect(config("money.currencies"))
			->keys()
			->mapWithKeys(
				fn($currency) => [
					$currency =>
						config("money.currencies")[$currency]["name"] .
						" (" .
						config("money.currencies")[$currency]["symbol"] .
						")",
				]
			)
			->toArray();
	}

	public static function getColorCategory(CategoryType|string $category)
	{
		return match ($category) {
			CategoryType::INCOME, CategoryType::INCOME->value => "text-success",
			CategoryType::EXPENSE, CategoryType::EXPENSE->value => "text-danger",
			CategoryType::TRANSFER, CategoryType::TRANSFER->value => "text-info",
		};
	}

	public static function accountTypeMap(?string $type = null)
	{
		$typeMap = [
			"cash" => [
				"icon" => "bi-cash-stack",
				"color" => "#10b981",
				"label" => "Uang Tunai",
				"placeholder" => "Tidak ada (Uang Tunai)",
			],
			"bank" => [
				"icon" => "bi-bank",
				"color" => "#3b82f6",
				"label" => "Bank",
				"placeholder" => "Contoh: BCA, Mandiri, BNI",
			],
			"ewallet" => [
				"icon" => "bi-phone",
				"color" => "#8b5cf6",
				"label" => "E-Wallet",
				"placeholder" => "Contoh: Dana, OVO, GoPay",
			],
			"credit_card" => [
				"icon" => "bi-credit-card",
				"color" => "#ef4444",
				"label" => "Kartu Kredit",
				"placeholder" => "Contoh: BCA Credit, Mandiri Credit",
			],
			"investment" => [
				"icon" => "bi-graph-up",
				"color" => "#f59e0b",
				"label" => "Investasi",
				"placeholder" => "Contoh: Reksadana, Saham, Crypto",
			],
		];

		return isset($type) ? $typeMap[$type] : $typeMap;
	}

	public static function categoriesIconList(): array
	{
		return [
			"bi-activity",
			"bi-alarm",
			"bi-arrow-down-circle",
			"bi-arrow-down-right-circle",
			"bi-arrow-up-circle",
			"bi-arrow-up-right-circle",
			"bi-bag",
			"bi-bag-check",
			"bi-bank",
			"bi-bar-chart",
			"bi-basket",
			"bi-basket2",
			"bi-bicycle",
			"bi-book",
			"bi-book-half",
			"bi-briefcase",
			"bi-briefcase-fill",
			"bi-bus-front",
			"bi-building",
			"bi-calendar",
			"bi-calendar-check",
			"bi-calendar-month",
			"bi-calendar-week",
			"bi-camera-reels",
			"bi-capsule",
			"bi-capsule-pill",
			"bi-cart",
			"bi-cart-check",
			"bi-cart-x",
			"bi-car-front",
			"bi-car-front-fill",
			"bi-cash-stack",
			"bi-cash-coin",
			"bi-check-circle",
			"bi-clock",
			"bi-clock-history",
			"bi-coin",
			"bi-controller",
			"bi-cup",
			"bi-cup-hot",
			"bi-cup-straw",
			"bi-currency-exchange",
			"bi-dice-5",
			"bi-droplet",
			"bi-egg",
			"bi-egg-fill",
			"bi-egg-fried",
			"bi-exclamation-circle",
			"bi-exclamation-triangle",
			"bi-file-earmark-text",
			"bi-file-text",
			"bi-film",
			"bi-fire",
			"bi-fuel-pump",
			"bi-fuel-pump-diesel",
			"bi-gear",
			"bi-gear-fill",
			"bi-gem",
			"bi-gift",
			"bi-graph-up",
			"bi-graph-up-arrow",
			"bi-heart",
			"bi-heart-fill",
			"bi-heart-pulse",
			"bi-house",
			"bi-house-check",
			"bi-house-door",
			"bi-hospital",
			"bi-info-circle",
			"bi-journal",
			"bi-journal-bookmark",
			"bi-laptop",
			"bi-lightning-charge",
			"bi-mic",
			"bi-mortarboard",
			"bi-music-note-beamed",
			"bi-pencil",
			"bi-pencil-square",
			"bi-phone",
			"bi-pie-chart",
			"bi-piggy-bank",
			"bi-prescription",
			"bi-prescription2",
			"bi-question-circle",
			"bi-receipt",
			"bi-receipt-cutoff",
			"bi-safe",
			"bi-shield-check",
			"bi-shield-exclamation",
			"bi-speedometer",
			"bi-tag",
			"bi-tag-fill",
			"bi-tags",
			"bi-tools",
			"bi-train-front",
			"bi-trophy",
			"bi-tv",
			"bi-wallet",
			"bi-wallet2",
			"bi-wifi",
			"bi-wrench",
			"bi-x-circle",
		];
	}
}
