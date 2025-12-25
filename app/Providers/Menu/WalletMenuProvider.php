<?php
namespace Modules\Wallet\Providers\Menu;

use Modules\Wallet\Constants\Permissions;
use Modules\MenuManagement\Providers\BaseMenuProvider;

class WalletMenuProvider extends BaseMenuProvider
{
	protected array $config = [
		"group" => "application",
		"location" => "sidebar",
		"icon" => "fas fa-grip",
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
				"icon" => "fas fa-dollar",
				"type" => "dropdown",
				"order" => 10,
				"children" => [
					$this->item([
						"title" => "Accounts",
						"icon" => "fas fa-user-circle",
						"route" => "apps.accounts.index",
						"order" => 1,
						"permission" => Permissions::VIEW_ACCOUNTS,
					]),
					$this->item([
						"title" => "Wallets",
						"icon" => "fas fa-wallet",
						"route" => "apps.wallets.index",
						"order" => 2,
						"permission" => Permissions::VIEW_WALLETS,
					]),
				],
			]),
		];
	}
}
