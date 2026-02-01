<?php
namespace Modules\Wallet\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class TelegramNotificationEvent
{
	use Dispatchable, InteractsWithSockets, SerializesModels;

	public $user;
	public $type;
	public $data;
	public $options;

	public function __construct(
		User $user,
		string $type,
		array $data = [],
		array $options = []
	) {
		$this->user = $user;
		$this->type = $type;
		$this->data = $data;
		$this->options = $options;
	}
}
