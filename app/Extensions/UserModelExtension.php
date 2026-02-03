<?php
namespace Modules\Wallet\Extensions;

use Illuminate\Database\Eloquent\Models;

class UserModelExtension
{
	public function handle(Model $model)
	{
		$model->mergeFillable([
			"telegram_verification_code",
			"telegram_code_expires_at",
			"telegram_notifications",
			"telegram_settings",
		]);
	}
}
