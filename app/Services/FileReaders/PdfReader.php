<?php
namespace Modules\Wallet\Services\FileReaders;

use Smalot\PdfParser\Parser;
use Smalot\PdfParser\Config;
use Modules\Wallet\Interfaces\FileReaderInterface;
use Modules\Wallet\Interfaces\BankStatementParserInterface;

class PdfReader implements FileReaderInterface
{
	protected string $inputFilename;
	protected ?string $password;
	protected array $bankParsers;
	protected bool $debug;

	public function __construct(string $filepath, ?string $password = null)
	{
		$this->inputFilename = $filepath;
		$this->password = $password;
		$this->registerBankParsers();
		$this->debug = config("financial.debug");
	}

	protected function registerBankParsers(): void
	{
		foreach (config("financial.bank_parsers") as $parser) {
			$this->bankParsers[] = new $parser();
		}
	}

	public function read(): array
	{
		if (!$this->inputFilename) {
			throw new \Exception("Filepath or filename not set yet.");
		}
		$tempFile = $this->decryptPdfFile();

		$pages = $this->extractTextFromPdf($tempFile);

		if ($this->debug) {
			logger()->debug("Extract text length: " . count($pages));
		}

		$parser = $this->findMatchingParser($pages[0]->getText());
		if (!$parser) {
			unlink($tempFile);
			throw new \Exception(
				"No suitable bank statement parser found for this PDF. Try another bank!"
			);
		}

		// Convert text to structured array
		$data = [];
		foreach ($pages as $page) {
			$data = array_merge($data, $parser->parse($page->getText()));
		}

		if ($this->debug) {
			logger()->debug("Parsed transactions count: " . count($data));
		}

		unlink($tempFile);
		return $data;
	}

	public function delete(): bool
	{
		if (!$this->inputFilename) {
			return true;
		}

		return unlink($this->inputFilename);
	}

	protected function decryptPdfFile(): string
	{
		// Decrypt PDF if password protected
		$tempFile = tempnam(sys_get_temp_dir(), "decrypted_pdf_");

		if ($this->password) {
			$command = "qpdf --password='{$this->password}' --decrypt '{$this->inputFilename}' '{$tempFile}'";
		} else {
			$command = "qpdf --decrypt '{$this->inputFilename}' '{$tempFile}'";
		}

		exec($command, $output, $returnCode);

		if ($returnCode !== 0) {
			unlink($tempFile);
			throw new \Exception(
				"Failed to decrypt PDF file. Wrong password or corrupted file."
			);
		}

		return $tempFile;
	}

	/**                                                                     * return array of page result
	 */
	private function extractTextFromPdf(string $pdfPath): array
	{
		$config = new Config();
		$config->setFontSpaceLimit(-50);
		$config->setHorizontalOffset("");

		$parser = new Parser([], $config);
		$pdf = $parser->parseFile($pdfPath);

		$pages = $pdf->getPages();

		return $pages ?: [];
	}

	private function findMatchingParser(
		string $content
	): ?BankStatementParserInterface {
		foreach ($this->bankParsers as $parser) {
			if ($parser->matches($content)) {
				return $parser;
			}
		}
		return null;
	}

	private function parseMandiriStatement(string $content): array
	{
		$lines = explode("\n", trim($content));
		$transactions = [];
		$inTransactionSection = false;
		$currentTransaction = [];
		$transactionCount = 0;
		foreach ($lines as $line) {
			$line = trim($line);

			// Skip empty lines and header/footer information
			if (
				empty($line) ||
				strpos($line, "Plaza Mandiri") !== false ||
				strpos($line, "e-Statement") !== false ||
				strpos($line, "Nama/Name") !== false ||
				strpos($line, "Cabang/Branch") !== false ||
				strpos($line, "Periode/Period") !== false ||
				strpos($line, "Nomor Rekening/Account Number") !== false ||
				strpos($line, "Mata Uang/Currency") !== false ||
				strpos($line, "Saldo Awal/Initial Balance") !== false ||
				strpos($line, "Dana Masuk/Incoming Transactions") !== false ||
				strpos($line, "Dana Keluar/Outgoing Transactions") !== false ||
				strpos($line, "Saldo Akhir/Closing Balance") !== false ||
				strpos($line, "PT Bank Mandiri") !== false ||
				strpos($line, "Mandiri Call 14000") !== false ||
				strpos($line, "of 13") !== false ||
				strpos($line, "dari") !== false
			) {
				continue;
			}

			// Detect start of transaction section
			if (
				preg_match(
					"/No\s+Date\s+Tanggal\s+Saldo\s+\(IDR\)\s+Balance\s+\(IDR\)\s+Nominal\s+\(IDR\)\s+Amount\s+\(IDR\)\s+Keterangan\s+Remarks/i",
					$line
				)
			) {
				$inTransactionSection = true;
				continue;
			}
			// Skip column headers
			if (
				preg_match(
					"/^\s*(No|Date|Tanggal|Saldo|Balance|Nominal|Amount|Keterangan|Remarks)/i",
					$line
				)
			) {
				continue;
			}

			if ($inTransactionSection) {
				// Check if this line contains transaction data
				if ($this->isTransactionLine($line)) {
					// If we have a complete previous transaction, save it
					if (!empty($currentTransaction)) {
						$transactions[] = $currentTransaction;
						$currentTransaction = [];
					}

					$transactionData = $this->parseTransactionLine($line);
					if (!empty($transactionData)) {
						$currentTransaction = $transactionData;
						$transactionCount++;
					}
				} elseif (!empty($currentTransaction)) {
					// This is a continuation line for the current transaction (description)
					$currentTransaction["description"] .= " " . $line;
					$currentTransaction["description"] = trim(
						$currentTransaction["description"]
					);
				}
			}
		}

		// Don't forget the last transaction
		if (!empty($currentTransaction)) {
			$transactions[] = $currentTransaction;
		}

		return $transactions;
	}

	private function isTransactionLine(string $line): bool
	{
		// Transaction lines typically contain dates and amounts
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

	private function parseTransactionLine(string $line): array
	{
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
			// Remove date from line to make further parsing easier                                                                                       $line = str_replace($dateMatches[1], "", $line);
		}

		// Extract time (format: 07:02:54 WIB)
		if (preg_match("/(\d{2}:\d{2}:\d{2}\s+WIB)/", $line, $timeMatches)) {
			$transaction["time"] = $timeMatches[1];
			$line = str_replace($timeMatches[1], "", $line);
		}

		// Extract amount (format: -2.800,00 or +49.940.475,00)
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

		// Clean up description - remove excess numbers and special characters
		$description = preg_replace("/^\d+\s*/", "", $description); // Remove leading numbers (transaction sequence)
		$description = preg_replace("/\b\d{10,}\b/", "", $description); // Remove long numbers (account numbers)
		$description = trim($description);

		$transaction["description"] = $description;

		return $transaction;
	}
}
