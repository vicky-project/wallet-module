<?php

namespace Modules\Wallet\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Wallet\Models\Account;
use Modules\Wallet\Services\ReportService;

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
	}

	public function monthlyReport(
		Request $request,
		int $year,
		int $month
	): JsonResponse {
		$request->validate([
			"month" => "integer|between:1,12",
			"year" => "integer|min:2000|max:2100",
		]);

		$data = $this->reportService->getMonthlyReport(auth()->id(), $year, $month);

		return response()->json([
			"success" => true,
			"data" => $data,
		]);
	}

	public function yearlyReport(Request $request, int $year): JsonResponse
	{
		$request->validate([
			"year" => "integer|min:2000|max:2100",
		]);

		$data = $this->reportService->getYearlyReport(auth()->id(), $year);

		return response()->json([
			"success" => true,
			"data" => $data,
		]);
	}

	public function customReport(Request $request): JsonResponse
	{
		$request->validate([
			"report_type" =>
				"required|in:financial_summary,income_expense_trend,category_analysis,budget_analysis,account_analysis,transaction_analysis",
			"start_date" => "nullable|date",
			"end_date" => "nullable|date|after_or_equal:start_date",
			"type" => "nullable|in:income,expense",
			"group_by" => "nullable|in:day,week,month",
			"period" => "nullable|in:current,monthly,yearly",
			"limit" => "nullable|integer|min:1|max:50",
		]);

		$data = $this->reportService->getCustomReport(
			auth()->id(),
			$request->report_type,
			$request->only([
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
			"start_date" => "required|date",
			"end_date" => "required|date|after_or_equal:start_date",
			"format" => "nullable|in:json,pdf,csv",
		]);

		$data = $this->reportService->getExportData(
			auth()->id(),
			$request->only(["start_date", "end_date"])
		);

		if ($request->format === "pdf") {
			// Generate PDF report
			return $this->generatePdfReport($data);
		} elseif ($request->format === "csv") {
			// Generate CSV report
			return $this->generateCsvReport($data);
		}

		return response()->json([
			"success" => true,
			"data" => $data,
		]);
	}

	private function generatePdfReport(array $data)
	{
		// Implementation for PDF generation
		// You can use DomPDF, TCPDF, or Laravel Excel
		return response()->json(["message" => "PDF export coming soon"]);
	}

	private function generateCsvReport(array $data)
	{
		// Implementation for CSV generation
		return response()->json(["message" => "CSV export coming soon"]);
	}
}
