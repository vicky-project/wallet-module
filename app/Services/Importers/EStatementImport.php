<?php
namespace Modules\Wallet\Services\Importers;
use Carbon\Carbon;
use Modules\Wallet\Models\Category;
use Modules\Wallet\Enums\TransactionType;

class EStatementImport extends BaseImporter
{
	protected string $locale = "Asia/Jakarta";

	protected array $headerMapping = [];

	public function load(): array
	{
		if ($this->data->isEmpty()) {
			return [];
		}

		return $this->data
			->filter(fn($row) => !empty(array_filter($row)))
			->map(fn($row) => $this->processRow($row))
			->all();
	}

	protected function processRow(array $row): array
	{
		$description = $this->cleanDescriptionParts($row["description"]);
		$category = null;
		if ($this->shouldCreateCategory()) {
			$category = $this->guessCategoryFromDescription(
				$description,
				$row["amount"]
			);
		}

		return [
			"user_id" => auth()->id(),
			"account_id" => $this->account->id,
			"category_id" => $category ? $this->getCategoryId($category) : null,
			"transaction_date" => $this->parseDate($row["date"] . " " . $row["time"]),
			"type" => $category ? $category["type"] : TransactionType::EXPENSE,
			"description" => $description,
			"amount" => $this->normalizeAmount($row["amount"]),
			"notes" => null,
			"created_at" => now(),
			"updated_at" => now(),
		];
	}

	public function setLocale(string $locale): self
	{
		$this->locale = $locale;
		return $this;
	}

	private function getCategoryId(array $category): int
	{
		return Category::firstOrCreate([
			"user_id" => auth()->id(),
			"name" => $category["name"],
			"type" => $category["type"],
		])->id;
	}

	private function parseDate(string $date): string
	{
		return Carbon::createFromFormat(
			"d M Y H:i:s T",
			$date,
			$this->locale
		)->format("Y-m-d H:i:s");
	}

	private function cleanDescriptionParts(string $description): string
	{
		$cleaned = preg_replace("/^\d+\s+dari\s+/", "", $description);
		$cleaned = preg_replace("/\d+\s*/", "", $cleaned);

		if (preg_match("/[A-Z]/u", $cleaned, $matches, PREG_OFFSET_CAPTURE)) {
			$firstCapitalPos = $matches[0][1];
			if ($firstCapitalPos > 0) {
				$cleaned = substr($cleaned, $firstCapitalPos);
			}
		}

		return trim($cleaned);
	}

	private function guessCategoryFromDescription(
		string $description,
		$amount
	): array {
		$description = str($description);
		$guessName = config("wallet.guess_category_by_text");

		if ($description->contains($guessName["admin"])) {
			return ["name" => "Admin", "type" => TransactionType::EXPENSE];
		}
		if ($description->contains($guessName["pulsa"])) {
			return ["name" => "Pulsa", "type" => TransactionType::EXPENSE];
		}
		if ($description->contains($guessName["tarik_tunai"])) {
			return ["name" => "Tarik Tunai", "type" => TransactionType::EXPENSE];
		}
		if ($description->contains($guessName["rumah"])) {
			return ["name" => "Rumah", "type" => TransactionType::EXPENSE];
		}
		if ($description->contains($guessName["belanja"])) {
			return ["name" => "Shop/E-Walet", "type" => TransactionType::EXPENSE];
		}
		if ($description->contains($guessName["transfer"])) {
			return [
				"name" =>
					"Transfer " . (str($amount)->startsWith("-") ? "Keluar" : "Masuk"),
				"type" => str($amount)->startsWith("-")
					? TransactionType::EXPENSE
					: TransactionType::INCOME,
			];
		}

		return ["name" => "Unknown", "type" => TransactionType::EXPENSE];
	}

	private function normalizeAmount(string $amount): string
	{
		return str($amount)
			->ltrim("-")
			->ltrim("+")
			->replace(".", "")
			->beforeLast(",");
	}
}
