<?php

namespace Modules\Core\Services;

use GuzzleHttp\Client;
use Nwidart\Modules\Facades\Module;
use Spatie\Packagist\PackagistUrlGenerator;
use Spatie\Packagist\PackagistClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class PackagistService
{
	protected $packagist;

	public function __construct()
	{
		$client = new Client();
		$generator = new PackagistUrlGenerator();

		$this->packagist = new PackagistClient($client, $generator);
	}

	public function getPackagesByVendor($vendor): array
	{
		$cacheKey = config("core.cache_key_prefix", "") . "_packagist";

		return Cache::remember($cacheKey, now()->addHours(24), function () use (
			$vendor
		) {
			try {
				return $this->packagist->getPackagesNamesByVendor($vendor)[
					"packageNames"
				];
			} catch (\Exception $e) {
				logger()->error("Packagist API error: " . $e->getMessage());
				return [];
			}
		});
	}

	public function getPackage(string $packageName): ?array
	{
		$cacheKey =
			cache("core.cache_key_prefix", "") . "_packagist_{$packageName}";

		return Cache::remember($cacheKey, now()->addHours(24), function () use (
			$packageName
		) {
			try {
				return $this->packagist->getPackage($packageName)["package"];
			} catch (\Exception $e) {
				logger()->error(
					"Packagist package error for {$packageName}: " . $e->getMessage()
				);
				return null;
			}
		});
	}

	public function getPackageVersionInfo(string $name)
	{
		$packageData = $this->getPackage($name);
		if (!$packageData) {
			return null;
		}

		$latestVersion = $this->getLatestStableVersion($packageData);
		$installedVersion = $this->getInstalledVersion($name);
		$isInstalled = $installedVersion !== null;
		$updateAvailable = false;
		if ($installedVersion && $latestVersion) {
			$normalizedInstalled = ltrim($installedVersion, "v");
			$normalizedLatest = ltrim($latestVersion, "v");

			$updateAvailable = version_compare(
				$normalizedInstalled,
				$normalizedLatest,
				"<"
			);

			logger()->debug("Version compare for {$name}:", [
				"installed" => $normalizedInstalled,
				"latest" => $normalizedLatest,
				"update_available" => $updateAvailable,
			]);
		}

		$moduleStatus = "not_installed";
		if ($this->isLocalModule($name)) {
			$moduleName = $this->extractModuleNameFromPackage($name);
			$module = Module::find($moduleName);
			$moduleStatus = $module->isEnabled() ? "enabled" : "disabled";
		}

		return [
			"name" => $packageData["name"],
			"description" => $packageData["description"] ?? "No description.",
			"repository" => $packageData["repository"] ?? "",
			"downloads" => $packageData["downloads"] ?? [],
			"favers" => $packageData["favers"] ?? 0,
			"github_stars" => $packageData["github_stars"] ?? 0,
			"type" => $packageData["type"] ?? "library",
			"latest_version" => $latestVersion,
			"installed_version" => $installedVersion,
			"is_installed" => $isInstalled,
			"update_available" => $updateAvailable,
			"module_status" => $moduleStatus,
			"is_local_module" => $this->isLocalModule($name),
			"time" => $packageData["time"] ?? now()->toISOString(),
		];
	}

	public function getLatestStableVersion(array $packageData): ?string
	{
		if (!$packageData || !isset($packageData["versions"])) {
			return nulll;
		}

		$allVersions = $packageData["versions"];
		$stableVersion = [];

		foreach ($allVersions as $version => $data) {
			if (preg_match("/dev|alpha|beta|rc/i", $version)) {
				continue;
			}

			if (!preg_match("/^\d+\.\d+\.\d+/", $version)) {
				continue;
			}

			$stableVersion[] = $version;
		}

		if (!empty($stableVersion)) {
			usort($stableVersion, "version_compare");
			return end($stableVersion);
		}
		return null;
	}

	public function getInstalledVersion(string $packageName): ?string
	{
		$composerVersion = $this->getComposerInstalledVersion($packageName);
		if ($composerVersion) {
			return $composerVersion;
		}

		$moduleVersion = $this->getLocalModuleVersion($packageName);
		if ($moduleVersion) {
			return $moduleVersion;
		}

		return null;
	}

	private function getComposerInstalledVersion($packageName)
	{
		$composerLockPath = base_path("composer.lock");

		if (!file_exists($composerLockPath)) {
			return null;
		}

		$cacheKey =
			config("core.cache_key_prefix", "") . "_package_installed_{$packageName}";

		return Cache::remember($cacheKey, now()->addHours(24), function () use (
			$packageName,
			$composerLockPath
		) {
			try {
				$composerLock = json_decode(file_get_contents($composerLockPath), true);

				foreach ($composerLock["packages"] as $package) {
					if ($package["name"] === $packageName) {
						return ltrim($package["version"] ?? null, "v");
					}
				}

				return null;
			} catch (\Exception $e) {
				logger()->error(
					"Error reading composer.lock for {$packageName}: " . $e->getMessage()
				);
				return null;
			}
		});
	}

	private function getLocalModuleVersion($packageName)
	{
		$moduleName = $this->extractModuleNameFromPackage($packageName);

		if (Module::has($moduleName)) {
			$module = Module::find($moduleName);

			$moduleJsonPath = $module->getPath() . "/module.json";
			if (file_exists($moduleJsonPath)) {
				$moduleJson = json_decode(file_get_contents($moduleJsonPath), true);
				return $moduleJson["version"] ?? "1.0.0";
			}

			$composerPath = $module->getPath() . "/composer.json";
			if (file_exists($composerPath)) {
				$composerContent = json_decode(file_get_contents($composerPath), true);
				return $composerContent["version"] ?? "1.0.0";
			}

			return "1.0.0";
		}

		return null;
	}

	public function getVendorPackageWithVersionInfo($vendor)
	{
		$packageResult = $this->getPackagesByVendor($vendor);
		$packagesInfo = [];

		foreach ($packageResult as $package) {
			$packageInfo = $this->getPackageVersionInfo($package);
			if ($packageInfo) {
				$packagesInfo[] = $packageInfo;
			}
		}

		return $packagesInfo;
	}

	/**
	 * Extract module name from package name
	 */
	public function extractModuleNameFromPackage($packageName)
	{
		$parts = explode("/", $packageName);
		$name = end($parts);

		// Convert kebab-case to StudlyCase (laravel-module -> LaravelModule)
		return str_replace(" ", "", ucwords(str_replace("-", " ", $name)));
	}

	/**
	 * Check if a package corresponds to a local module
	 */
	public function isLocalModule($packageName)
	{
		$moduleName = $this->extractModuleNameFromPackage($packageName);
		return Module::has($moduleName);
	}
}
