<?php

namespace Modules\Core\Services;

use Nwidart\Modules\Facades\Module;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class InstallationService
{
	public function processPostInstallation($moduleName)
	{
		Log::info("Starting post-installation process for: {$moduleName}");

		$module = Module::find($moduleName);

		if (!$module) {
			throw new \Exception(
				"Module {$moduleName} not found after installation."
			);
		}

		try {
			// Enable module
			$module->enable();
			Log::info("Module enabled: {$moduleName}");

			// Run pre-installation hooks from module
			$this->runModulePreInstallation($module);

			// Publish assets
			$this->publishAssets($module);

			// Run migrations
			$this->runMigrations($module);

			// Run seeders
			$this->runSeeders($module);

			// Run post-installation hooks
			$this->runModulePostInstallation($module);

			// Optimize application
			$this->optimizeApplication($module);

			Log::info("Post-installation completed successfully for: {$moduleName}");
		} catch (\Exception $e) {
			Log::error(
				"Post-installation failed for {$moduleName}: " . $e->getMessage()
			);
			throw $e;
		}
	}

	/**
	 * Modules\{ModuleName}\Installation\PreInstallation::class
	 * nwthod handle()
	 */
	protected function runModulePreInstallation($module)
	{
		$preInstallationClass = "Modules\\{$module->getName()}\\Installation\\PreInstallation";

		if (class_exists($preInstallationClass)) {
			Log::info("Running pre-installation for: {$module->getName()}");
			$preInstallation = app($preInstallationClass);
			$preInstallation->handle();
		}
	}

	/**
	 * Modules\{ModuleName}\Installation\PostInstallation::class
	 * method handle()
	 */
	protected function runModulePostInstallation($module)
	{
		$postInstallationClass = "Modules\\{$module->getName()}\\Installation\\PostInstallation";

		if (class_exists($postInstallationClass)) {
			Log::info("Running post-installation for: {$module->getName()}");
			$postInstallation = app($postInstallationClass);
			$postInstallation->handle();
		}
	}

	protected function publishAssets($module)
	{
		Log::info("Publishing assets for: {$module->getName()}");
		Artisan::call("module:publish", ["module" => $module->getName()]);
	}

	protected function runMigrations($module)
	{
		Log::info("Running migrations for: {$module->getName()}");
		Artisan::call("module:migrate", [
			"module" => $module->getName(),
			"--force" => true,
		]);
	}

	protected function runSeeders($module)
	{
		$seederClass = "Modules\\{$module->getName()}\\Database\\Seeders\\{$module->getName()}Seeder";

		if (class_exists($seederClass)) {
			Log::info("Running seeder for: {$module->getName()}");
			Artisan::call("db:seed", [
				"--class" => $seederClass,
				"--force" => true,
			]);
		}
	}

	protected function optimizeApplication($module)
	{
		Log::info("Optimizing application after module installation");

		Artisan::call("optimize:clear");
		Artisan::call("module:discover");
		Artisan::call("config:cache");
		Artisan::call("route:cache");

		// Run module-specific optimizations
		$this->runModuleOptimizations($module);
	}

	/**
	 * Modules\{ModuleName}\Service\DatabaseOptimizer::class
	 * method optimize()
	 */
	protected function runModuleOptimizations($module)
	{
		$optimizerClass = "Modules\\{$module->getName()}\\Services\\DatabaseOptimizer";

		if (class_exists($optimizerClass)) {
			Log::info("Running database optimization for: {$module->getName()}");
			$optimizer = app($optimizerClass);
			$optimizer->optimize();
		}
	}
}
