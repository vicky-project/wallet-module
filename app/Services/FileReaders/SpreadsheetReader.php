<?php
namespace Modules\Wallet\Services\FileReaders;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Modules\Wallet\Interfaces\FileReaderInterface;

class SpreadsheetReader implements FileReaderInterface
{
	protected $reader;
	protected string $inputFilename;

	public function __construct(string $filepath)
	{
		$this->inputFilename = $filepath;
		$filetype = IOFactory::identify($this->inputFilename);
		$this->reader = IOFactory::createReader($filetype);
	}
	public function read(): array
	{
		if (!$this->inputFilename) {
			throw new \Exception("Filepath or filename not set yet.");
		}

		$this->reader->setReadDataOnly(true);
		$sheet = $this->reader->load($this->inputFilename);
		$ws = $sheet->getActiveSheet();
		return $ws->toArray();
	}

	public function delete(): bool
	{
		if (!$this->inputFilename) {
			return true;
		}

		return unlink($this->inputFilename);
	}
}
