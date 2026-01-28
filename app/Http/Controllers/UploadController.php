<?php
namespace Modules\Wallet\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Wallet\Models\Account;
use Modules\Wallet\Models\Transaction;
use Modules\Wallet\Http\Requests\UploadRequest;
use Modules\Wallet\Services\AccountService;
use Modules\Wallet\Services\ImportServiceFactory;

class UploadController extends Controller
{
	public function __construct(protected AccountService $accountService)
	{
	}

	public function index(Request $request)
	{
		$user = $request->user();
		$accounts = Account::forUser($user->id)
			->active()
			->get();

		return view("wallet::upload", compact("accounts"));
	}

	public function upload(UploadRequest $request)
	{
		$validated = $request->validated();
		$appsName = $validated["apps_name"];
		$file = $validated["file"];
		$password = $validated["password"] ?? null;

		try {
			$account = Account::findOrFail($validated["account_id"]);
			$filestore = $file->store("upload", "public");
			$filepath = Storage::disk("public")->path($filestore);
			$fileType = $file->getClientOriginalExtension();

			$factory = ImportServiceFactory::createReader(
				$fileType,
				$filepath,
				$password
			);
			$data = $factory->read();

			$importer = ImportServiceFactory::createImporter(
				$appsName,
				$data,
				$account,
				$validated
			);
			$result = $importer->load();

			DB::transaction(function () use ($result) {
				Transaction::insert($result);
			});

			$this->accountService->recalculateBalance($account);
			Cache::flush();

			return back()->with("success", "Data was imported successfully");
		} catch (\Exception $e) {
			return back()->withErrors($e->getMessage());
		}
	}
}
