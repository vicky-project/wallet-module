<?php
namespace Modules\Wallet\Interfaces;

interface FileReaderInterface
{
	public function __construct(string $filepath);

	public function read(): array;

	public function delete(): bool;
}
