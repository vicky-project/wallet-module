<?php
namespace Modules\Wallet\Services\Importers;

use Illuminate\Support\Collection;
use Modules\Wallet\Models\Account;
use Modules\Wallet\Interfaces\ImporterInterface;

abstract class BaseImporter implements ImporterInterface
{
	protected Collection $data;
	protected Account $account;
	protected ?array $config;
	protected array $headerMapping = [];

	public function __construct(
		array $data,
		Account $account,
		?array $config = []
	) {
		$this->data = collect($data);
		$this->account = $account;
		$this->config = $config;
	}

	protected function mapHeaders(array $headerRow): array
	{
		$mapping = [];
		foreach ($this->headerMapping as $internalKey => $externalKey) {
			$mapping[$internalKey] = array_search($externalKey, $headerRow);
		}
		return $mapping;
	}

	protected function extractDataWithMapping(array $row, array $mapping): array
	{
		$extracted = [];

		foreach ($mapping as $internalKey => $externalIndex) {
			if ($externalIndex !== false && isset($row[$externalIndex])) {
				$extracted[$internalKey] = $row[$externalIndex];
			}
		}
		return $extracted;
	}

	protected function shouldCreateCategory(): bool
	{
		return isset($this->config["auto_create_categories"])
			? (bool) $this->config["auto_create_categories"]
			: false;
	}

	abstract protected function processRow(array $row): array;
}
