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

	public function searchLaravelModules(): array
	{
		$cacheKey = "packagist_laravel_module";

		return Cache::remember($cacheKey, now()->addHours(24), function () {
			try {
				return $this->packagist->getPackagesNamesByVendor("vicky-project");
			} catch (\Exception $e) {
				logger()->error("Packagist API error: " . $e->getMessage());
				return [];
			}
		});
	}

	protected function getLatestStableVersion(array $packageData): ?string
	{
		if (!$packageData || !isset($packageData["package"]["versions"])) {
			return null;
		}

		$allVersions = $packageData["package"]["versions"];
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

	public function getPackage(string $name): ?array
	{
		$cacheKey = "packagist_laravel_module_package_{$name}";

		return Cache::remember($cacheKey, now()->addHours(24), function () use (
			$name
		) {
			try {
				return $this->packagist->getPackage($name)["package"];
			} catch (\Exception $e) {
				logger()->error(
					"Packagist package error for {$name}: " . $e->getMessage()
				);
				return null;
			}
		});
	}

	public function getInstalledModule()
	{
		$composerLockPath = base_path("composer.lock");

		if (!file_exists($composerLockPath)) {
			return [];
		}
		try {
			$composerLock = json_decode(file_get_contents($composerLockPath), true);
			$moduleNames = [];

			foreach ($composerLock["packages"] ?? [] as $package) {
				if (($package["type"] ?? "") === "laravel-module") {
					$moduleNames[] = $package["name"];
				}
			}

			return $moduleNames;
		} catch (\Exception $e) {
			logger()->error(
				"Error reading installed module from composer.lock: " . $e->getMessage()
			);
			return [];
		}
	}
}
