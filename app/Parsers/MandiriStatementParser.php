<?php
namespace Modules\Wallet\Parsers;

use Illuminate\Support\Collection;

class MandiriStatementParser extends AbstractBankParser
{
	protected array $headerPatterns = [
		"No",
		"Date",
		"Tanggal",
		"Balance (IDR)",
		"SALDO (IDR)",
		"Tanggal\tSaldo (IDR)",
		"Nominal (IDR)",
		"Amount (IDR)",
		"Keterangan",
		"Remarks",
	];

	protected array $skipPatterns = [
		"Plaza Mandiri",
		"e-Statement",
		"Nama/Name",
		"Cabang/Branch",
		"Periode/Period",
		"Nomor Rekening/Account Number",
		"Mata Uang/Currency",
		"Saldo Awal/Initial Balance",
		"Dana Masuk/Incoming Transactions",
		"Dana Keluar/Outgoing Transactions",
		"Saldo Akhir/Closing Balance",
		"PT Bank Mandiri",
		"Mandiri Call 14000",
		" (LPS)",
		"KCP ",
		"of",
	];

	public function matches(string $content): bool
	{
		$mandiriPatterns = [
			"/Plaza Mandiri/",
			"/e-Statement/",
			"/Tabungan Mandiri/",
			"/Mandiri Call 14000/",
			"/PT Bank Mandiri.*OJK.*BI.*LPS/",
			"/Saldo Awal\/Initial Balance/",
			"/Dana Masuk\/Incoming Transactions/",
			"/Dana Keluar\/Outgoing Transactions/",
			"/Saldo Akhir\/Closing Balance/",
		];
		return collect($mandiriPatterns)
			->filter(fn($pattern) => preg_match($pattern, $content))
			->count() >= 3;
	}
	public function parse(string $content): array
	{
		$lines = $this->prepareLines($content);
		return $this->extractTransactions($lines);
	}

	private function prepareLines(string $content): array
	{
		return collect(explode("\n", $content))
			->map(fn($line) => trim($line))
			->filter(fn($line) => !empty($line))
			->slice(array_search("Tabungan Mandiri", explode("\n", $content)))
			->filter(fn($line) => !$this->shouldSkipLine($line))
			->filter(fn($line) => !in_array($line, $this->headerPatterns))
			->filter(fn($line) => $this->isRelevantLine($line))
			->values()
			->all();
	}

	private function extractTransactions(array $lines): array
	{
		if (empty($lines)) {
			dd($lines);
		}
		$transactions = [];
		$lastDateIndex = 0;
		foreach ($lines as $key => $line) {
			// Cari tanggal sebagai indikator
			if ($this->isDateLine($line)) {
				// Jika tidak ada deskripsi dan nominal berarti bukan transaksi
				if ($key - $lastDateIndex <= 2) {
					$lastDateIndex = $key;
					continue;
				}

				$currentTransaction = [];
				// Ambil data diatas tanggal sampai batas tanggal pada index sebelumnya
				for ($i = $key; $i > $lastDateIndex; $i--) {
					$currentTransaction[] = $lines[$i];
				}
				$transactions[] = $currentTransaction;
				// reset index terakhir untuk acuan dsta berikutnya
				$lastDateIndex = $key;
			}
		}

		return $this->populateDataTransaction($transactions);
	}

	private function populateDataTransaction(array $transactions): array
	{
		$data = [];
		foreach ($transactions as $transaction) {
			$currentTransaction = [];
			$currentDescriptions = [];
			foreach ($transaction as $line) {
				if ($this->isDateLine($line)) {
					$currentTransaction["date"] = $line;
					continue;
				}
				if ($this->isTimeLine($line)) {
					$currentTransaction["time"] = $line;
					continue;
				}
				if ($this->isAmountLine($line)) {
					$currentTransaction["amount"] = $this->extractAmount($line);
					$descriptionPart = $this->extractDescriptionFromAmountLine($line);
					if ($descriptionPart) {
						$currentDescriptions[] = $descriptionPart;
					}
					continue;
				}
				$currentDescriptions[] = $line;
			}

			$currentTransaction["description"] = $this->buildDescription(
				$currentDescriptions
			);
			$data[] = $currentTransaction;
		}

		return $data;
	}

	private function isRelevantLine(string $line): bool
	{
		if (str_contains($line, ":") && !$this->isTimeLine($line)) {
			return false;
		}
		if (
			str_contains($line, "-") &&
			!str_contains($line, "\t") &&
			!$this->isDateLine($line)
		) {
			return false;
		}

		return true;
	}

	private function extractAmount(string $line): string
	{
		$parts = explode("\t", $line);
		$amount = end($parts);

		return preg_replace("/[^\d.,+-]/", "", $amount);
	}

	private function extractDescriptionFromAmountLine(string $line): ?string
	{
		$parts = explode("\t", $line);
		if (count($parts) >= 3) {
			$description = $parts[0];
			return !empty(trim($description)) ? trim($description) : null;
		}
		return null;
	}

	private function buildDescription(array $descriptions): string
	{
		return collect($descriptions)
			->reverse()
			->filter()
			->map(fn($desc) => trim($desc))
			->implode(" ");
	}
	/**
	 * Check if line matches date pattern (e.g., "02 Jul 2025")
	 */
	private function isDateLine(string $line): bool
	{
		return (bool) preg_match('/^\d{1,2} [A-Za-z]{3} \d{4}$/', $line);
	}
	/**
	 * Check if line matches time pattern (e.g., "07:02:54 WIB")
	 */ private function isTimeLine(string $line): bool
	{
		return (bool) preg_match('/^\d{1,2}:\d{2}:\d{2} [A-Za-z]+$/', $line);
	}

	/**
	 * Check if line matches amount pattern (e.g., "2.669.917,521\t-2.800,00")
	 */
	private function isAmountLine(string $line): bool
	{
		return str_contains($line, "\t") &&
			preg_match('/[+-]?\d{1,3}(?:\.\d{3})*(?:,\d{2})$/', $line);
	}

	public function getBankName(): string
	{
		return "mandiri";
	}

	/**
	 * Check if line should be skipped.
	 */
	private function shouldSkipLine(string $line): bool
	{
		return collect($this->skipPatterns)->contains(
			fn($pattern) => str_contains($line, $pattern)
		);
	}
}
