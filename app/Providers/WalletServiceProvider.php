<?php

namespace Modules\Wallet\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schedule;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Illuminate\Database\Eloquent\Model;
use Modules\Telegram\Services\Handlers\CallbackHandler as TelegramCallbackHandler;
use Modules\Telegram\Services\Handlers\CommandDispatcher;
use Modules\Telegram\Services\Support\InlineKeyboardBuilder;
use Modules\Telegram\Services\Support\TelegramApi;
use Modules\Telegram\Services\TelegramService;
use Modules\Wallet\Services\AccountService;
use Modules\Wallet\Telegram\Callbacks\CallbackHandler;
use Modules\Wallet\Telegram\Commands\AccountCommand;
use Modules\Wallet\Telegram\Commands\AddCommand;
use Modules\Wallet\Telegram\Commands\CategoryCommand;
use Modules\Wallet\Telegram\Middlewares\CallbackMiddleware;

class WalletServiceProvider extends ServiceProvider
{
	use PathNamespace;

	protected string $name = "Wallet";

	protected string $nameLower = "wallet";

	/**
	 * Boot the application events.
	 */
	public function boot(): void
	{
		$this->registerCommands();
		$this->registerCommandSchedules();
		$this->registerTranslations();
		$this->registerConfig();
		$this->registerViews();
		$this->loadMigrationsFrom(module_path($this->name, "database/migrations"));

		Model::preventLazyLoading(!$this->app->isProduction());

		if ($this->app->bound(CommandDispatcher::class)) {
			$dispatcher = $this->app->make(CommandDispatcher::class);

			$this->registerTelegramCommands($dispatcher);
			$this->registerTelegramMiddlewares($dispatcher);
		} else {
			\Log::warning(
				"Telegram CommandDispatcher not bound. Skipping command registration."
			);
		}

		if ($this->app->bound(TelegramCallbackHandler::class)) {
			$callback = $this->app->make(TelegramCallbackHandler::class);
			$this->registerCallbackHandlers($callback);
			$this->registerCallbackMiddlewares($callback);
		}
	}

	protected function registerTelegramCommands(
		CommandDispatcher $dispatcher
	): void {
		$dispatcher->registerCommand(
			new AccountCommand(
				$this->app->make(TelegramService::class),
				$this->app->make(TelegramApi::class),
				$this->app->make(InlineKeyboardBuilder::class)
			),
			["auth"]
		);
		$dispatcher->registerCommand(
			new AddCommand(
				$this->app->make(TelegramApi::class),
				$this->app->make(TelegramService::class)
			),
			["auth"]
		);
		$dispatcher->registerCommand(
			new CategoryCommand(
				$this->app->make(TelegramService::class),
				$this->app->make(TelegramApi::class)
			),
			["auth"]
		);
	}

	protected function registerTelegramMiddlewares(
		CommandDispatcher $dispatcher
	): void {
		// $dispatcher->registerMiddleware();
	}

	protected function registerCallbackHandlers(
		TelegramCallbackHandler $callback
	): void {
		$callback->registerHandler(
			new CallbackHandler($this->app->make(TelegramApi::class)),
			["auth", "module-callback"]
		);
	}

	protected function registerCallbackMiddlewares(
		TelegramCallbackHandler $callback
	): void {
		$callback->registerMiddleware(
			"module-callback",
			new CallbackMiddleware(
				$this->app->make(AccountService::class),
				$this->app->make(TelegramService::class)
			)
		);
	}

	/**
	 * Register the service provider.
	 */
	public function register(): void
	{
		$this->app->register(EventServiceProvider::class);
		$this->app->register(RouteServiceProvider::class);
	}

	/**
	 * Register commands in the format of Command::class
	 */
	protected function registerCommands(): void
	{
		$this->commands([
			\Modules\Wallet\Console\ProcessRecurringTransactionsCommand::class,
			\Modules\Wallet\Console\CheckBudgetWarnings::class,
			\Modules\Wallet\Console\CheckLowBalances::class,
			\Modules\Wallet\Console\SendDailyTelegramSummary::class,
			\Modules\Wallet\Console\SendWeeklyTelegramReport::class,
		]);
	}

	/**
	 * Register command Schedules.
	 */
	protected function registerCommandSchedules(): void
	{
		$this->app->booted(function () {
			//$schedule = $this->app->make(Schedule::class);
			Schedule::command("app:process-recurring")
				->dailyAt("00:01")
				->withoutOverlapping();
			Schedule::command("telegram:daily-summary")
				->dailyAt("20:00")
				->timezone(config("app.timezone"));
			Schedule::command("telegram:check-budgets")
				->dailyAt("09:00")
				->timezone(config("app.timezone"));
			Schedule::command("telegram:check-balances")
				->dailyAt("10:00")
				->timezone(config("app.timezone"));
			Schedule::command("telegram:weekly-report")
				->weeklyOn(0, "19:00")
				->timezone(config("app.timezone"));
		});
	}

	/**
	 * Register translations.
	 */
	public function registerTranslations(): void
	{
		$langPath = resource_path("lang/modules/" . $this->nameLower);

		if (is_dir($langPath)) {
			$this->loadTranslationsFrom($langPath, $this->nameLower);
			$this->loadJsonTranslationsFrom($langPath);
		} else {
			$this->loadTranslationsFrom(
				module_path($this->name, "lang"),
				$this->nameLower
			);
			$this->loadJsonTranslationsFrom(module_path($this->name, "lang"));
		}
	}

	/**
	 * Register config.
	 */
	protected function registerConfig(): void
	{
		$configPath = module_path(
			$this->name,
			config("modules.paths.generator.config.path")
		);

		if (is_dir($configPath)) {
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($configPath)
			);

			foreach ($iterator as $file) {
				if ($file->isFile() && $file->getExtension() === "php") {
					$config = str_replace(
						$configPath . DIRECTORY_SEPARATOR,
						"",
						$file->getPathname()
					);
					$config_key = str_replace(
						[DIRECTORY_SEPARATOR, ".php"],
						[".", ""],
						$config
					);
					$segments = explode(".", $this->nameLower . "." . $config_key);

					// Remove duplicated adjacent segments
					$normalized = [];
					foreach ($segments as $segment) {
						if (end($normalized) !== $segment) {
							$normalized[] = $segment;
						}
					}

					$key =
						$config === "config.php"
							? $this->nameLower
							: implode(".", $normalized);

					$this->publishes(
						[$file->getPathname() => config_path($config)],
						"config"
					);
					$this->merge_config_from($file->getPathname(), $key);
				}
			}
		}
	}

	/**
	 * Merge config from the given path recursively.
	 */
	protected function merge_config_from(string $path, string $key): void
	{
		$existing = config($key, []);
		$module_config = require $path;

		config([$key => array_replace_recursive($existing, $module_config)]);
	}

	/**
	 * Register views.
	 */
	public function registerViews(): void
	{
		$viewPath = resource_path("views/modules/" . $this->nameLower);
		$sourcePath = module_path($this->name, "resources/views");

		$this->publishes(
			[$sourcePath => $viewPath],
			["views", $this->nameLower . "-module-views"]
		);

		$this->loadViewsFrom(
			array_merge($this->getPublishableViewPaths(), [$sourcePath]),
			$this->nameLower
		);

		Blade::componentNamespace(
			config("modules.namespace") . "\\" . $this->name . "\\View\\Components",
			$this->nameLower
		);
	}

	/**
	 * Get the services provided by the provider.
	 */
	public function provides(): array
	{
		return [];
	}

	private function getPublishableViewPaths(): array
	{
		$paths = [];
		foreach (config("view.paths") as $path) {
			if (is_dir($path . "/modules/" . $this->nameLower)) {
				$paths[] = $path . "/modules/" . $this->nameLower;
			}
		}

		return $paths;
	}
}
