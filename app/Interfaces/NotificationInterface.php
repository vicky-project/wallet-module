<?php
namespace Modules\Wallet\Interfaces;

use App\Models\User;

interface NotificationInterface
{
	public function send(User $user): bool;
	public function getType(): string;
	public function shouldSend(User $user): bool;
	public function buildMessage(): string;
	public function buildKeyboard(): ?array;
}
