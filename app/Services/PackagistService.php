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

	public function getModule($page = 1, $perPage = 12)
	{
		$cacheKey = "packagist_laravel_module_{$page}_{$perPage}";

		return Cache::remember($cacheKey, now()->addHours(24), function () use (
			$page,
			$perPage
		) {
			try {
				$package = $this->packagist->getPackagesNamesByVendor("spatie");
				dd($package);

				return collect($data["results"] ?? [])->map(function ($package) {
					return [
						"name" => $package["name"],
						"description" => $package["description"] ?? "No description",
						"url" => $package["url"],
						"repository" => $package["repository"] ?? "",
						"downloads" => $package["downloads"] ?? 0,
						"favers" => $package["favers"] ?? 0,
						"type" => "packagist",
					];
				});
			} catch (\Exception $e) {
				logger()->error("Packagist API error: " . $e->getMessage());
			}

			return collect();
		});
	}
}
