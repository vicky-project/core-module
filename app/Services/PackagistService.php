<?php

namespace Modules\Core\Services;

use GuzzleHttp\Client;
use Spatie\Packagist\PackagistUrlGenerator;
use Spatie\Packagist\PackagistClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class PackagistService
{
	protected $packagist;

	public function __construct()
	{
		$client = new Client();
		$generator = new PackagistUrlGenerator();

		$this->packagist = new PackagistClient($client, $generator);
	}

	public function getModules(): Collection
	{
		$cacheKey = "packagist_laravel_module";

		return Cache::remember($cacheKey, now()->addHours(24), function () {
			try {
				$data = $this->packagist->getPackagesNamesByVendor("vicky-project");

				return collect($data["packageNames"] ?? [])
					->map(fn($package) => $this->getModule($package))
					->map(function ($package) {
						$package["latest_version"] = $this->getLatestStableVersion(
							$package["versions"]
						);
						return $package;
					});
			} catch (\Exception $e) {
				logger()->error("Packagist API error: " . $e->getMessage());
			}

			return collect();
		});
	}

	protected function getLatestStableVersion(array $packages): array
	{
		return array_filter(
			$packages,
			fn($version) => !preg_match("/dev|alpha|beta|rc/i", $version)
		);
	}

	public function getModule(string $name): array
	{
		$cacheKey = "packagist_laravel_module_package_{$name}";

		return Cache::remember($cacheKey, now()->addHours(24), function () use (
			$name
		) {
			return $this->packagist->getPackage($name)["package"];
		});
	}
}
