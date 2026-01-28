<?php
namespace Modules\Wallet\Services;

use Modules\Wallet\Models\Account;
use Modules\Wallet\Services\Importers\FireflyImport;
use Modules\Wallet\Services\Importers\VickyserverImport;
use Modules\Wallet\Services\Importers\EStatementImport;
use Modules\Wallet\Interfaces\FileReaderInterface;
use Modules\Wallet\Interfaces\ImporterInterface;
use Modules\Wallet\Services\FileReaders\PdfReader;
use Modules\Wallet\Services\FileReaders\SpreadsheetReader;

class ImportServiceFactory
{
	public static function createReader(
		string $fileType,
		string $filepath,
		?string $password = null
	): FileReaderInterface {
		return match (strtolower($fileType)) {
			"pdf" => new PdfReader($filepath, $password),
			"xlsx", "xls", "csv", "ods" => new SpreadsheetReader($filepath),
			default => throw new \InvalidArgumentException(
				"Unsupported file type: {$fileType}"
			),
		};
	}

	public static function createImporter(
		string $appName,
		array $data,
		Account $account,
		?array $config = []
	): ImporterInterface {
		$importerClass = match ($appName) {
			"firefly" => FireflyImport::class,
			"vickyserver" => VickyserverImport::class,
			"e-statement" => EStatementImport::class,
			default => throw new \InvalidArgumentException(
				"Unsupported app: {$appName}"
			),
		};

		return new $importerClass($data, $account, $config);
	}
}
