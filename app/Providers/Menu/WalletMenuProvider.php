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
						"title" => "Wallets",
						"icon" => "fas fa-wallet",
						"route" => "apps.financial",
						"order" => 2,
						"permission" => Permissions::VIEW_ACCOUNTS,
					]),
					$this->item([
						"title" => "Transactions",
						"icon" => "fas fa-arrow-right-arrow-left",
						"route" => "apps.transactions.index",
						"order" => 3,
						"permission" => Permissions::VIEW_TRANSACTIONS,
					]),
					$this->item([
						"title" => "Category",
						"icon" => "fas fa-tags",
						"route" => "apps.categories.index",
						"order" => 4,
						"permission" => Permissions::VIEW_CATEGORIES,
					]),
				],
			]),
		];
	}
}
