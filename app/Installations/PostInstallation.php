<?php
namespace Modules\Wallet\Installations;

use Nwidart\Modules\Facades\Module;
use Illuminate\Support\Facades\Artisan;

class PostInstallation
{
	public function handle(string $moduleName)
	{
		try {
			exec("which qpdf", $output, $returnCode);

			if ($returnCode !== 0) {
				throw new \Exception(
					"This module required qpdf installed. Please find the way to install it in yout server."
				);
			}

			$module = Module::find($moduleName);
			$module->enable();

			Artisan::call("vendor:publish", [
				"--tag" => ["laravel-wallet-migration", "laravel-wallet-config"],
			]);
			Artisan::call("migrate", ["--force" => true]);
		} catch (\Exception $e) {
			logger()->error(
				"Failed to run post installation of financial module: " .
					$e->getMessage()
			);

			throw $e;
		}
	}
}
