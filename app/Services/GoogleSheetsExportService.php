<?php

namespace Modules\Wallet\Services;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission;
use Google\Service\Sheets;

class GoogleSheetsExportService
{
	protected $client;
	protected $drive;
	protected $sheets;

	public function __construct()
	{
		$this->client = new Client();
		// Sesuaikan path ke file JSON kredensial Anda
		$this->client->setAuthConfig(
			storage_path("app/google-service-account.json")
		);
		$this->client->addScope([Drive::DRIVE, Sheets::SPREADSHEETS]);
		$this->drive = new Drive($this->client);
		$this->sheets = new Sheets($this->client);
	}

	/**
	 * Membuat spreadsheet Google Sheets dari konten CSV.
	 *
	 * @param string $csvContent Data dalam format CSV.
	 * @param string $title Judul spreadsheet.
	 * @return \Google\Service\Drive\DriveFile
	 */
	public function createSpreadsheetFromCsv(
		$csvContent,
		$title = "Laporan Keuangan"
	) {
		// 1. Upload file CSV ke Drive
		$fileMetadata = new DriveFile([
			"name" => $title . ".csv",
			"mimeType" => "text/csv",
		]);

		$file = $this->drive->files->create($fileMetadata, [
			"data" => $csvContent,
			"mimeType" => "text/csv",
			"uploadType" => "multipart",
			"fields" => "id",
		]);

		$csvFileId = $file->getId();

		// 2. Konversi CSV ke format Google Sheets
		$sheetMetadata = new DriveFile([
			"name" => $title,
			"mimeType" => "application/vnd.google-apps.spreadsheet",
		]);

		$spreadsheet = $this->drive->files->copy($csvFileId, $sheetMetadata, [
			"fields" => "id, webViewLink",
		]);

		// 3. Berikan izin "reader" untuk siapa saja yang memiliki link
		$permission = new Permission([
			"type" => "anyone",
			"role" => "reader",
		]);

		$this->drive->permissions->create($spreadsheet->getId(), $permission);

		// 4. Hapus file CSV temporary
		$this->drive->files->delete($csvFileId);

		return $spreadsheet;
	}

	/**
	 * Helper: Konversi array data laporan menjadi string CSV.
	 */
	public function convertReportDataToCsv(array $reportData): string
	{
		// Implementasi konversi data laporan Anda ke format CSV
		// Contoh sederhana:
		$rows = [];
		// ... proses data reportData menjadi array of rows ...
		// Untuk contoh, kita buat CSV sederhana
		$rows[] = ["Bulan", "Pendapatan", "Pengeluaran"];
		// ... isi data ...

		$output = fopen("php://temp", "r+");
		foreach ($rows as $row) {
			fputcsv($output, $row);
		}
		rewind($output);
		$csv = stream_get_contents($output);
		fclose($output);

		return $csv;
	}
}
