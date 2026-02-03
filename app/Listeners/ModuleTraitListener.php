<?php
namespace Modules\Wallet\Listeners;

use Modules\Core\Events\ModuleTraitEvent;

class ModuleTraitListener
{
	public function handle(ModuleTraitEvent $event)
	{
		$model = $event->model;
	}
}
