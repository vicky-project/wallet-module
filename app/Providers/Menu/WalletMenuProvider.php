<?php
namespace Modules\Wallet\Providers\Menu;

use Modules\Wallet\Constants\Permissions;
use Modules\MenuManagement\Providers\BaseMenuProvider;

class WalletMenuProvider extends BaseMenuProvider
{
	protected array $config = [
		"group" => "application",
		"location" => "sidebar",
		"icon" => "bi bi-grip",
		"order" => 1,
		"permission" => null,
	];

	public function __construct()
	{
		$moduleName = "Wallet";
		parent::__construct($moduleName);
	}

	/**
	 * Get all menus
	 */
	public function getMenus(): array
	{
		return [
			$this->item([
				"title" => "Financial",
				"icon" => "bi bi-currency-dollar",
				"order" => 10,
				"route" => "apps.financial",
				"permission" => Permissions::VIEW_ACCOUNTS,
			]),
		];
	}
}
