<?php
namespace Modules\Wallet\Services\Telegram\Handlers\Callbacks;

use Illuminate\Support\Collection;
use Modules\Wallet\Interfaces\CallbackHandlerInterface;

class CallbackHandlerFactory
{
	protected Collection $handlers;

	public function __construct()
	{
		$this->handlers = collect([
			"budget" => BudgetCallbackHandler::class,
			"account" => AccountCallbackHandler::class,
			"transaction" => TransactionCallbackHandler::class,
			"notification" => NotificationCallbackHandler::class,
			"funds" => FundsCallbackHandler::class,
			"report" => ReportCallbackHandler::class,
		]);
	}

	public function make(string $type): ?CallbackHandlerInterface
	{
		if (!$this->handlers->has($type)) {
			return null;
		}

		$handlerClass = $this->handlers->get($type);
		return app($handlerClass);
	}

	public function getHandlerForCallback(
		string $action,
		string $type
	): ?CallbackHandlerInterface {
		foreach ($this->handlers as $handlerClass) {
			$handler = app($handlerClass);
			if ($handler->supports($action, $type)) {
				return $handler;
			}
		}
		return null;
	}
}
