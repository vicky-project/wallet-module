<?php
namespace Modules\Wallet\Services\Importers;

use Illuminate\Support\Str;
use Modules\Wallet\Models\Category;
use Modules\Wallet\Enums\TransactionType;

class FireflyImport extends BaseImporter
{
	protected array $headerMapping = [
		"amount" => "amount",
		"description" => "description",
		"date" => "date",
		"notes" => "notes",
		"category" => "category",
	];

	public function load(): array
	{
		if ($this->data->isEmpty()) {
			return [];
		}

		$headerRow = [];
		if (!$this->shouldSkipHeader()) {
			$headerRow = $this->data->shift();
		}

		$mapping = $this->mapHeaders($headerRow);

		return $this->data
			->filter(fn($row) => !empty(array_filter($row)))
			->map(function ($row) use ($mapping) {
				$extracted = $this->extractDataWithMapping($row, $mapping);
				return $this->processRow($extracted);
			})
			->all();
	}

	protected function processRow(array $row): array
	{
		$categoryId = null;
		if ($this->shouldCreateCategory()) {
			$categoryId = $this->getCategoryId($row["category"], $row["amount"]);
		}

		return [
			"uuid" => Str::uuid(),
			"user_id" => auth()->id(),
			"account_id" => $this->account->id,
			"category_id" => $categoryId ?? null,
			"transaction_date" => now()
				->parse($row["date"])
				->format("Y-m-d H:i:s"),
			"type" => $this->determineTransactionType($row["amount"]),
			"description" => $row["description"] ?? "",
			"amount" => $this->normalizeAmount($row["amount"]),
			"notes" => $row["notes"] ?? null,
			"created_at" => now(),
			"updated_at" => now(),
		];
	}

	private function getCategoryId(string $category, string $amount): int
	{
		$type = $this->determineTransactionType($amount);

		if ($category === "Transfer") {
			$category = str_starts_with($amount, "-")
				? "Transfer Keluar"
				: "Transfer Masuk";
		}

		return Category::firstOrCreate([
			"user_id" => auth()->id(),
			"name" => $category,
			"type" => $type,
		])->id;
	}

	private function determineTransactionType($amount): TransactionType
	{
		return str_starts_with($amount, "-")
			? TransactionType::EXPENSE
			: TransactionType::INCOME;
	}

	private function normalizeAmount(string $amount): string
	{
		return str_starts_with($amount, "-") ? substr($amount, 1) : $amount;
	}
}
