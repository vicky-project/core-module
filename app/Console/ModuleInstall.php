<?php

namespace Modules\Core\Console;

use Nwidart\Modules\Facades\Module;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ModuleInstall extends Command
{
	/**
	 * The name and signature of the console command.
	 */
	protected $signature = "app:install {module}";

	/**
	 * The console command description.
	 */
	protected $description = "Running installation module application.";

	/**
	 * Create a new command instance.
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 */
	public function handle()
	{
		$moduleName = $this->argument("module");
		$module = Module::find($moduleName);

		if (!$module) {
			$this->error("Module {$moduleName} nit found.");
			return;
		}

		$this->info("Installing module {$module->getName()}...");

		$module->enable();

		$postInstallationClass = "Modules\\{$module->getName()}\\Installations\\PostInstallation";

		if (class_exists($postInstallationClass)) {
			$this->info("Found installer. Running process...");
			$postInstallation = app($postInstallationClass);
			$postInstallation->handle($module->getName());
			$thi->info("Process completed.");
		}

		$this->info("Installation successful");
	}

	/**
	 * Get the console command arguments.
	 */
	protected function getArguments(): array
	{
		return [["module", InputArgument::REQUIRED, "Module name to be install."]];
	}

	/**
	 * Prompt for missing input arguments using the returned questions.
	 *
	 * @return array<string, string>
	 */
	protected function promptForMissingArgumentsUsing(): array
	{
		return [
			"module" => "Which module should be install?",
		];
	}
}
