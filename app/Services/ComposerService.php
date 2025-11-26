<?php

namespace Modules\Core\Services;

use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log;

class ComposerService
{
	public function requirePackage($packageName, $version = null)
	{
		$package = $version ? "{$packageName}:{$version}" : $packageName;

		$process = new Process([
			"composer",
			"require",
			$package,
			"--no-interaction",
		]);
		$process->setTimeout(300); // 5 minutes
		$process->setWorkingDirectory(base_path());

		$process->run();

		if (!$process->isSuccessful()) {
			Log::error("Composer require failed: " . $process->getErrorOutput());
			throw new \Exception(
				"Package installation failed: " . $process->getErrorOutput()
			);
		}

		Log::info("Composer require successful: {$package}");
		return true;
	}

	public function validatePackage($packageName, $version = null)
	{
		// Check if package exists via Packagist API
		$url = "https://repo.packagist.org/p2/{$packageName}.json";

		$client = new \GuzzleHttp\Client();
		try {
			$response = $client->get($url);
			$data = json_decode($response->getBody(), true);

			if (!isset($data["packages"][$packageName])) {
				throw new \Exception("Package {$packageName} not found on Packagist.");
			}

			if (
				$version &&
				!$this->versionExists($data["packages"][$packageName], $version)
			) {
				throw new \Exception(
					"Version {$version} not found for package {$packageName}."
				);
			}

			return true;
		} catch (\Exception $e) {
			throw new \Exception("Package validation failed: " . $e->getMessage());
		}
	}

	protected function versionExists($packageData, $version)
	{
		foreach ($packageData as $versionData) {
			if (
				$versionData["version"] === $version ||
				$versionData["version_normalized"] === $version
			) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Remove package via composer
	 */
	public function removePackage($packageName)
	{
		$process = new Process([
			"composer",
			"remove",
			$packageName,
			"--no-interaction",
		]);
		$process->setTimeout(300);
		$process->setWorkingDirectory(base_path());

		$process->run();

		if (!$process->isSuccessful()) {
			throw new \Exception(
				"Composer remove failed: " . $process->getErrorOutput()
			);
		}

		Log::info("Composer remove successful: {$packageName}");
		return true;
	}

	/**
	 * Run composer install
	 */
	public function install()
	{
		$process = new Process(["composer", "install", "--no-interaction"]);
		$process->setTimeout(300);
		$process->setWorkingDirectory(base_path());

		$process->run();

		if (!$process->isSuccessful()) {
			throw new \Exception(
				"Composer install failed: " . $process->getErrorOutput()
			);
		}

		Log::info("Composer install successful");
		return true;
	}

	/**
	 * Dump autoload files
	 */
	public function dumpAutoload()
	{
		$process = new Process(["composer", "dump-autoload"]);
		$process->setTimeout(120);
		$process->setWorkingDirectory(base_path());

		$process->run();

		if (!$process->isSuccessful()) {
			throw new \Exception(
				"Composer dump-autoload failed: " . $process->getErrorOutput()
			);
		}

		Log::info("Composer dump-autoload successful");
		return true;
	}
}
