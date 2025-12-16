<?php

namespace Modules\Wallet\Providers;

use Modules\MenuManagement\Interfaces\MenuProviderInterface;
use Modules\Wallet\Constants\Permissions;

class MenuProvider implements MenuProviderInterface
{
	/**
	 * Get Menu for LogManagement Module.
	 */
	public static function getMenus(): array
	{
		return [
			[
				"id" => "financial",
				"name" => "Financial",
				"order" => 20,
				"icon" => "dollar",
				"role" => "user",
				"type" => "group",
				"children" => [
					[
						"id" => "financial-account",
						"name" => "Accounts",
						"order" => 5,
						"icon" => "wallet",
						"route" => "apps.wallet.index",
						"role" => "user",
						"permission" => Permissions::VIEW_ACCOUNTS,
					],
				],
			],
		];
	}
}
