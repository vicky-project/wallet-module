<?php
namespace Modules\Financial\Interfaces;

interface BankStatementParserInterface
{
	/**
	 * Check if the content matches this bank's statement format
	 */
	public function matches(string $content): bool;

	/**
	 * Parse the content and return structured transaction data
	 */
	public function parse(string $content): array;

	/**
	 * Get bank name identifier
	 */
	public function getBankName(): string;
}
