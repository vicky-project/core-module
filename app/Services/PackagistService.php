<?php

namespace Modules\Core\Services;

use GuzzleHttp\Client;
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
		$cacheKey = config("core.cache_key_prefix") . "_packagist";

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

	protected function getPackage(string $packageName): ?array
	{
		$cacheKey = cache("core.cache_key_prefix") . "_packagist_{$packageName}";

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
			"is_installed" => $installedVersion !== null,
			"update_available" =>
				$installedVersion &&
				$latestVersion &&
				version_compare($installedVersion, $latestVersion, "<"),
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
		$composerLockPath = base_path("composer.lock");

		if (!file_exists($composerLockPath)) {
			return null;
		}
		$cacheKey = config("api.cache_key_prefix") . "_packagist_{$packageName}";

		try {
			return Cache::remember($cacheKey, now()->addHours(24), function () use (
				$packageName
			) {
				$composerLock = json_decode(file_get_contents($composerLockPath), true);
				foreach ($composerLock["packages"] ?? [] as $package) {
					if ($package["name"] === $packageName) {
						return ltrim($package["version"] ?? null, "v");
					}
				}

				foreach ($composerLock["packages-dev"] ?? [] as $package) {
					if ($package["name"] === $packageName) {
						return ltrim($package["version"] ?? null, "v");
					}
				}

				return null;
			});
		} catch (\Exception $e) {
			logger()->error(
				"Error reading composer.lock for {$packageName}: " . $e->getMessage()
			);
			return null;
		}
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
}
