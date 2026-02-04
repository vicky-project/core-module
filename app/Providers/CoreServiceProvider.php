<?php

namespace Modules\Core\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Modules\Core\Services\BackupService;
use Modules\Core\Services\ComposerService;
use Modules\Core\Services\ModuleManagerService;
use Modules\Core\Service\NotificationService;

class CoreServiceProvider extends ServiceProvider
{
	use PathNamespace;

	protected string $name = "Core";

	protected string $nameLower = "core";

	/**
	 * Boot the application events.
	 */
	public function boot(): void
	{
		if (config("app.env") === "production") {
			URL::forceScheme("https");
		}

		$this->registerCommands();
		$this->registerCommandSchedules();
		$this->registerTranslations();
		$this->registerConfig();
		$this->registerViews();
		$this->loadMigrationsFrom(module_path($this->name, "database/migrations"));

		$this->registerBlades();
	}

	/**
	 * Register the service provider.
	 */
	public function register(): void
	{
		$this->app->register(EventServiceProvider::class);
		$this->app->register(RouteServiceProvider::class);

		$this->app->singleton(NotificationService::class, function ($app) {
			return new NotificationService();
		});

		$this->app->singleton(ModuleManagerService::class, function ($app) {
			return new ModuleManagerService(
				$app->make(BackupService::class),
				$app->make(ComposerService::class)
			);
		});

		$this->app->singleton(BackupService::class);
	}

	protected function registerBlades(): void
	{
		Blade::component("core::components.sidebar", "core-sidebar");
		Blade::component("core::components.navbar", "core-navbar");
		Blade::component("core::components.footer", "core-footer");
		Blade::component("core::components.breadcrumb", "core-breadcrumb");
		Blade::component("core::components.alert", "core-alert");

		Blade::directive("hook", function ($expression) {
			return "<?php echo \Modules\Core\Service\HookService::render($expression); ?>";
		});

		Blade::directive("hasHook", function ($expression) {
			return "<?php if(\Modules\Core\Service\HookService::has($expression)): ?>";
		});

		Blade::directive("endHasHook", function () {
			return "<?php endif; ?>";
		});
	}

	/**
	 * Register commands in the format of Command::class
	 */
	protected function registerCommands(): void
	{
		$this->commands([
			\Modules\Core\Console\ModuleInstall::class,
			\Modules\Core\Console\ViewCommand::class,
		]);
	}

	/**
	 * Register command Schedules.
	 */
	protected function registerCommandSchedules(): void
	{
		// $this->app->booted(function () {
		//     $schedule = $this->app->make(Schedule::class);
		//     $schedule->command('inspire')->hourly();
		// });
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

					if ($config === "backup.php") {
						$this->mergeConfigFrom($file->getPathname(), "backup");
					}
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
