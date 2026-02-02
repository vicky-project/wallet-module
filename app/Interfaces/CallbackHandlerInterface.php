<?php
namespace Modules\Wallet\Interfaces;

use App\Models\User;

interface CallbackHandlerInterface
{
	public function handle(User $user, array $data, $callbackQuery): array;
	public function supports(string $action, string $type): bool;
}
