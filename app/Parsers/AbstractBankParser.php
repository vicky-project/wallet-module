<?php
namespace Modules\Wallet\Parsers;

use Modules\Wallet\Interfaces\BankStatementParserInterface;

abstract class AbstractBankParser implements BankStatementParserInterface
{
	protected function isTransactionLine(string $line): bool
	{
		// Default implementation - can be overridden by child classes
		$patterns = [
			"/\d{1,2}\s+(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+\d{4}/i",
			"/[+-]?\d{1,3}(?:\.\d{3})*(?:,\d{2})/",
			"/\d{2}:\d{2}:\d{2}\s+WIB/",
		];

		foreach ($patterns as $pattern) {
			if (preg_match($pattern, $line)) {
				return true;
			}
		}

		return false;
	}

	protected function parseTransactionLine(string $line): array
	{
		// This is a fallback implementation
		// Child classes should override this for bank-specific formats
		$transaction = [
			"date" => "",
			"description" => "",
			"amount" => "",
			"time" => "",
		];

		// Extract date (format: 02 Jul 2025)
		if (
			preg_match(
				"/(\d{1,2}\s+(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+\d{4})/i",
				$line,
				$dateMatches
			)
		) {
			$transaction["date"] = $dateMatches[1];
			$line = str_replace($dateMatches[1], "", $line);
		}

		// Extract time (format: 07:02:54 WIB)
		if (preg_match("/(\d{2}:\d{2}:\d{2}\s+WIB)/", $line, $timeMatches)) {
			$transaction["time"] = $timeMatches[1];
			$line = str_replace($timeMatches[1], "", $line);
		}

		// Extract amount
		if (
			preg_match(
				"/([+-]?\d{1,3}(?:\.\d{3})*(?:,\d{2}))/",
				$line,
				$amountMatches
			)
		) {
			$transaction["amount"] = $amountMatches[1];
			$line = str_replace($amountMatches[1], "", $line);
		}

		// The remaining text is the description
		$description = trim(preg_replace("/\s+/", " ", $line));
		$description = preg_replace("/^\d+\s*/", "", $description);
		$description = preg_replace("/\b\d{10,}\b/", "", $description);
		$description = trim($description);

		$transaction["description"] = $description;

		return $transaction;
	}

	/**
	 * Helper method to normalize amount format
	 */
	protected function normalizeAmount(string $amount): float
	{
		// Remove dots (thousands separator) and replace comma (decimal separator) with dot                                                           $normalized = str_replace(".", "", $amount);
		$normalized = str_replace(",", ".", $normalized);

		return (float) $normalized;
	}
	/**
	 * Helper method to convert date to standard format
	 */
	protected function normalizeDate(string $date): string
	{
		// Convert "02 Jul 2025" to "2025-07-02"                               $timestamp = strtotime($date);
		if ($timestamp !== false) {
			return date("Y-m-d", $timestamp);
		}

		return $date;
	}
}
