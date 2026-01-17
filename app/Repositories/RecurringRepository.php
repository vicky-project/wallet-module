<?php
namespace Modules\Wallet\Repositories;

use App\Models\User;
use Carbon\Carbon;
use Modules\Wallet\Models\RecurringTransaction;

class RecurringRepository extends BaseRepository
{
	public function __construct(RecurringTransaction $model)
	{
		parent::__construct($model);
	}

	public function getDashboardData(User $user, Carbon $now)
	{
		return ["upcoming" => []];
	}
}
