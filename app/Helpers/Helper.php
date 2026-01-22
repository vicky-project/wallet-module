<?php
namespace Modules\Wallet\Helpers;

use Brick\Money\Money;
use Modules\Wallet\Enums\CategoryType;

class Helper
{
	/**
	 * Generate cache key based on parameters
	 */
	public static function generateCacheKey(
		string $prefix,
		array $params = []
	): string {
		return $prefix . "_" . md5(serialize($params));
	}

	public static function toMoney(
		string|int $money,
		string $currency = "IDR",
		bool $isInt = false
	): Money {
		$currency = $currency ?? config("wallet.default_currency", "USD");
		return $isInt
			? Money::of($money, $currency)
			: Money::ofMinor($money, $currency);
	}

	public static function formatMoney(int|string|float $money)
	{
		if (!is_int($money)) {
			$money = (int) $money;
		}

		return number_format($money, 0, ",", ".");
	}

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

	public static function accountTypeMap(?string $type = null): array
	{
		$typeMap = [
			"cash" => [
				"icon" => "bi-cash-stack",
				"color" => "#10b981",
				"label" => "Uang Tunai",
				"bankPlaceholder" => "Tidak ada (Uang Tunai)",
			],
			"bank" => [
				"icon" => "bi-bank",
				"color" => "#3b82f6",
				"label" => "Bank",
				"bankPlaceholder" => "Contoh: BCA, Mandiri, BNI",
			],
			"ewallet" => [
				"icon" => "bi-phone",
				"color" => "#f59e0b",
				"label" => "E-Wallet",
				"bankPlaceholder" => "Contoh: Dana, OVO, GoPay",
			],
			"credit_card" => [
				"icon" => "bi-credit-card",
				"color" => "#ef4444",
				"label" => "Kartu Kredit",
				"bankPlaceholder" => "Contoh: BCA Credit, Mandiri Credit",
			],
			"investment" => [
				"icon" => "bi-graph-up",
				"color" => "#8b5cf6",
				"label" => "Investasi",
				"bankPlaceholder" => "Contoh: Reksadana, Saham, Crypto",
			],
			"savings" => [
				"icon" => "bi-piggy-bank",
				"color" => "#a81cb6",
				"label" => "Tabungan",
				"bankPlaceholder" => "Tidak ada (Celengan)",
			],
			"other" => [
				"icon" => "bi-coin",
				"color" => "#cdba3d",
				"label" => "Lainnya",
				"bankPlaceholder" => "Tidak ada (Lainnya)",
			],
		];

		return isset($type) && !is_null($type) ? $typeMap[$type] : $typeMap;
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
			"bi-bank2",
			"bi-bar-chart",
			"bi-bar-chart-fill",
			"bi-bar-chart-line",
			"bi-bar-chart-line-fill",
			"bi-basket",
			"bi-basket-fill",
			"bi-basket2",
			"bi-basket2-fill",
			"bi-basket3",
			"bi-basket3-fill",
			"bi-bicycle",
			"bi-book",
			"bi-book-half",
			"bi-box",
			"bi-box-fill",
			"bi-box2",
			"bi-box2-fill",
			"bi-box2-heart",
			"bi-box2-heart-fill",
			"box-steam",
			"box-steam-fill",
			"bi-briefcase",
			"bi-briefcase-fill",
			"bi-building",
			"bi-bus-front",
			"bi-bus-front-fill",
			"bi-cake",
			"bi-cake-fill",
			"bi-cake2",
			"bi-cake2-fill",
			"bi-calendar",
			"bi-calculator",
			"bi-calculator-fill",
			"bi-calendar-check",
			"bi-calendar-check-fill",
			"bi-calendar-date",
			"bi-calendar-date-fill",
			"bi-calendar-day",
			"bi-calendar-day-fill",
			"bi-calendar-heart",
			"bi-calendar-heart-fill",
			"bi-calendar-month",
			"bi-calendar-month-fill",
			"bi-calendar-week",
			"bi-calendar-week-fill",
			"bi-camera",
			"bi-camera2",
			"bi-camera-fill",
			"bi-camera-reels",
			"bi-camera-video",
			"bi-capsule",
			"bi-capsule-pill",
			"bi-cart",
			"bi-cart-check",
			"bi-cart-dash",
			"bi-cart-x",
			"bi-cart2",
			"bi-cart3",
			"bi-cart4",
			"bi-car-front",
			"bi-car-front-fill",
			"bi-cash",
			"bi-cash-stack",
			"bi-cash-coin",
			"bi-check-circle",
			"bi-clock",
			"bi-clock-fill",
			"bi-clock-history",
			"bi-cloud",
			"bi-coin",
			"bi-controller",
			"bi-cpu",
			"bi-cup",
			"bi-cup-fill",
			"bi-cup-hot",
			"bi-cup-straw",
			"bi-currency-bitcoin",
			"bi-currency-exchange",
			"bi-database",
			"bi-dice-5",
			"bi-display",
			"bi-dropbox",
			"bi-droplet",
			"bi-earbuds",
			"bi-egg",
			"bi-egg-fill",
			"bi-egg-fried",
			"bi-envelope",
			"bi-envelope-at",
			"bi-envelope-heart",
			"bi-exclamation-circle",
			"bi-exclamation-triangle",
			"bi-ev-station",
			"bi-facebook",
			"bi-fan",
			"bi-file-earmark-text",
			"bi-file-text",
			"bi-file-person",
			"bi-file-person-fill",
			"bi-film",
			"bi-fire",
			"bi-fuel-pump",
			"bi-fuel-pump-diesel",
			"bi-gear",
			"bi-gear-fill",
			"bi-gem",
			"bi-gift",
			"bi-github",
			"bi-google",
			"bi-google-play",
			"bi-gpu-card",
			"bi-graph-up",
			"bi-graph-up-arrow",
			"bi-hammer",
			"bi-headphones",
			"bi-headset",
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
			"bi-journals",
			"bi-keyboard",
			"bi-lamp",
			"bi-laptop",
			"bi-lightning-charge",
			"bi-lungs",
			"bi-mic",
			"bi-mortarboard",
			"bi-motherboard",
			"bi-music-note-beamed",
			"bi-nvidia",
			"bi-openai",
			"bi-paperclip",
			"bi-pencil",
			"bi-pencil-square",
			"bi-phone",
			"bi-pie-chart",
			"bi-piggy-bank",
			"bi-pinterest",
			"bi-play-btn",
			"bi-playstation",
			"bi-playstation",
			"bi-plus-slash-minus",
			"bi-prescription2",
			"bi-printer",
			"bi-projector",
			"bi-qr-code",
			"bi-question-circle",
			"bi-receipt",
			"bi-receipt-cutoff",
			"bi-recycle",
			"bi-rocket",
			"bi-router",
			"bi-safe",
			"bi-server",
			"bi-shield-check",
			"bi-shield-exclamation",
			"bi-speedometer",
			"bi-spotify",
			"bi-steam",
			"bi-strava",
			"bi-suitcase",
			"bi-suitcase-lg",
			"bi-suitcase2",
			"bi-tablet",
			"bi-tag",
			"bi-tag-fill",
			"bi-tags",
			"bi-telegram",
			"bi-telephone-outbound",
			"bi-thermometer",
			"bi-tiktok",
			"bi-tools",
			"bi-train-front",
			"bi-tree",
			"bi-trophy",
			"bi-truck",
			"bi-tv",
			"bi-twitter",
			"bi-twitter-x",
			"bi-umbrella",
			"bi-usb-drive",
			"bi-valentine",
			"bi-valentine2",
			"bi-vimeo",
			"bi-wallet",
			"bi-wallet2",
			"bi-water",
			"bi-webcam",
			"bi-whatsapp",
			"bi-wifi",
			"bi-windows",
			"bi-wrench",
			"bi-x-circle",
			"bi-youtube",
		];
	}
}
