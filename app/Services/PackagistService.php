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
		$cacheKey = "packagist_laravel_module";

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

	protected function getPackage(string $packageName)
	{
		$cacheKey = "packagist_package_{$packageName}";

		return Cache::remember($cacheKey, now()->addHours(24), function () use (
			$packageName
		) {
			try {
				return $this->packagist->getPackage($packageName);
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
		dd($packageData);
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
