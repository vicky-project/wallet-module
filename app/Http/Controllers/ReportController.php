<?php

namespace Modules\Wallet\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Wallet\Models\Account;
use Modules\Wallet\Services\ReportService;
use Modules\Wallet\Services\Exporters\FinancialReportExport;

class ReportController extends Controller
{
	protected $reportService;

	public function __construct(ReportService $reportService)
	{
		$this->reportService = $reportService;
	}

	public function index(Request $request)
	{
		$user = $request->user();
		$accounts = Account::where("user_id", $user->id)
			->active()
			->orderBy("name")
			->get(["id", "name", "balance"]);
		return view("wallet::reports", compact("accounts"));
	}

	public function dashboardSummary(Request $request): JsonResponse
	{
		try {
			$request->validate([
				"start_date" => "nullable|date",
				"end_date" => "nullable|date|after_or_equal:start_date",
				"account_id" => "nullable|exists:accounts,id",
			]);

			$data = $this->reportService->getDashboardSummary(
				auth()->id(),
				$request->only(["start_date", "end_date", "account_id"])
			);

			return response()->json([
				"success" => true,
				"data" => $data,
			]);
		} catch (\Exception $e) {
			dd($e);
		}
	}

	public function monthlyReport(
		Request $request,
		int $year,
		int $month
	): JsonResponse {
		$request->validate([
			"account_id" => "nullable|integer|exists:accounts,id",
		]);

		$data = $this->reportService->getMonthlyReport(
			auth()->id(),
			$year,
			$month,
			$request->account_id
		);

		return response()->json([
			"success" => true,
			"data" => $data,
		]);
	}

	public function yearlyReport(Request $request, int $year): JsonResponse
	{
		$request->validate([
			"account_id" => "nullable|integer|exists:accounts,id",
		]);

		$data = $this->reportService->getYearlyReport(
			auth()->id(),
			$year,
			$request->account_id
		);

		return response()->json([
			"success" => true,
			"data" => $data,
		]);
	}

	public function customReport(Request $request): JsonResponse
	{
		$request->validate([
			"account_id" => "nullable|integer|exists:accounts,id",
			"report_type" =>
				"required|in:financial_summary,income_expense_trend,category_analysis,budget_analysis,account_analysis,transaction_analysis",
			"start_date" => "nullable|date",
			"end_date" => "nullable|date|after_or_equal:start_date",
			"type" => "nullable|in:income,expense",
			"group_by" => "nullable|in:day,week,month,year",
			"period" => "nullable|in:current,monthly,yearly",
			"limit" => "nullable|integer|min:1|max:50",
		]);

		$data = $this->reportService->getCustomReport(
			auth()->id(),
			$request->report_type,
			$request->only([
				"account_id",
				"start_date",
				"end_date",
				"type",
				"group_by",
				"period",
				"limit",
			])
		);

		return response()->json([
			"success" => true,
			"data" => $data,
		]);
	}

	public function exportReport(Request $request): JsonResponse
	{
		$request->validate([
			"account_id" => "nullable|exists:accounts,id",
			"start_date" => "required|date",
			"end_date" => "required|date|after_or_equal:start_date",
			"format" => "nullable|in:json,pdf,csv,xls,xlsx",
		]);

		$data = $this->reportService->getExportData(
			auth()->id(),
			$request->only(["account_id", "start_date", "end_date"])
		);

		return match ($request->format) {
			"json" => $this->exportToJson($data),
			"xls", "xlsx", "csv", "pdf" => $this->exportToFile(
				$data,
				$request->format
			),
			default => response()->json(
				[
					"success" => false,
					"message" => "Unsupported format: " . $request->format,
				],
				400
			),
		};
	}

	private function exportToJson(array $data): JsonResponse
	{
		return response()->json(["success" => true, "data" => $data]);
	}

	private function exportToFile(array $data, string $format): JsonResponse
	{
		try {
			$export = new FinancialReportExport($data);

			$writerType = match ($format) {
				"xls" => \Maatwebsite\Excel\Excel::XLS,
				"csv" => \Maatwebsite\Excel\Excel::CSV,
				"pdf" => \Maatwebsite\Excel\Excel::DOMPDF,
				default => \Maatwebsite\Excel\Excel::XLSX,
			};

			$filename =
				"laporan-keuangan-" . now()->format("d-m-Y-H-i-s") . "-vickyserver";
			$tempPath = storage_path("app/temp/" . $filename . "." . $format);

			if (!file_exists(dirname($tempPath))) {
				mkdir(dirname($tempPath), 0775, true);
			}

			Excel::store(
				$export,
				"temp/" . $filename . "." . $format,
				"local",
				$writerType
			);

			$fileContent = file_get_contents($tempPath);

			unlink($tempPath);

			if ($format === "pdf") {
				return reponse()->json([
					"success" => true,
					"download_url" =>
						"data:application/pdf;base64," . base64_encode($fileContent),
					"filename" => $filename . "." . $format,
					"mime_type" => "application/pdf",
					"has_charts" => false,
				]);
			}

			$mimeTypes = [
				"xlsx" =>
					"application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
				"xls" => "application/vnd.ms-excel",
				"csv" => "text/csv",
			];

			$mimeType = $mimeTypes[$format] ?? "application/octet-stream";

			return response()->json([
				"success" => true,
				"download_url" =>
					"data:" . $mimeType . ";base64," . base64_encode($fileContent),
				"filename" => $filename . "." . $format,
				"mime_type" => $mimeType,
				"has_charts" => $format === "xlsx" || $format === "xls",
			]);

			// Implementation for CSV generation
		} catch (\Exception $e) {
			return response()->json(
				[
					"success" => false,
					"error" => "Gagal menghasilkan file: " . $e->getMessage(),
					"trace" => $e->getTraceAsString(),
				],
				500
			);
		}
	}
}
