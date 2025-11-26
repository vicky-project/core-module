<?php

namespace Modules\Core\Services;

use GuzzleHttp\Client;
use Spatie\Packagist\PackagistClient;
use Spatie\Packagist\PackagistUrlGenerator;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log;
use Composer\Semver\Semver;

class ComposerService
{
	protected $packagistClient;

	public function __construct()
	{
		$client = new Client();
		$urlGenerator = new PackagistUrlGenerator();
		$this->packagistClient = new packagistClient($client, $urlGenerator);
	}

	/**
	 * Get package information dari Packagist
	 */
	public function getPackageInfo($packageName, $version = null)
	{
		try {
			Log::info("Fetching package info from Packagist: {$packageName}");

			// Gunakan spatie/packagist-api untuk mengambil data package
			$packageData = $this->packagistClient->getPackage($packageName);

			if (!isset($packageData["package"])) {
				throw new Exception("Package {$packageName} not found on Packagist");
			}

			$packageInfo = $packageData["package"];

			// Jika version specified, cari version specific info
			if ($version) {
				return $this->getSpecificVersionInfo($packageInfo, $version);
			}

			Log::info("Successfully retrieved package info for: {$packageName}");
			return $packageInfo;
		} catch (Exception $e) {
			Log::error(
				"Failed to get package info for {$packageName}: " . $e->getMessage()
			);
			throw new Exception(
				"Unable to retrieve package information: " . $e->getMessage()
			);
		}
	}

	/**
	 * Get specific version information
	 */
	protected function getSpecificVersionInfo($packageInfo, $version)
	{
		if (!isset($packageInfo["versions"])) {
			throw new Exception("No version information available for package");
		}

		// Normalize version name (remove 'v' prefix, etc.)
		$normalizedVersion = $this->normalizeVersion($version);

		foreach ($packageInfo["versions"] as $versionName => $versionData) {
			if (
				$versionName === $normalizedVersion ||
				$this->versionMatches($versionName, $version)
			) {
				return $versionData;
			}
		}

		throw new Exception("Version {$version} not found for package");
	}

	/**
	 * Normalize version string
	 */
	protected function normalizeVersion($version)
	{
		// Remove 'v' prefix from version tags
		return preg_replace("/^v/", "", $version);
	}

	/**
	 * Check if version matches constraint
	 */
	protected function versionMatches($versionName, $constraint)
	{
		try {
			return Semver::satisfies($versionName, $constraint);
		} catch (Exception $e) {
			return false;
		}
	}

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
