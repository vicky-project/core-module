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

	public function getModules()
	{
		$cacheKey = "packagist_laravel_module";

		return Cache::remember($cacheKey, now()->addHours(24), function () {
			try {
				$data = $this->packagist->getPackagesNamesByType("laravel-module");

				return collect($data["packageNames"] ?? [])->map(
					fn($package) => $this->packagist->getPackage($package)["package"]
				);
			} catch (\Exception $e) {
				logger()->error("Packagist API error: " . $e->getMessage());
			}

			return collect();
		});
	}

	public function getModule(string $name)
	{
		$cacheKey = "packagist_laravel_module_package_{$name}";

		return Cache::remember($cacheKey, now()->addHours(24), function () use (
			$name
		) {
			return $this->packagist->getPackage($name);
		});
	}
}
