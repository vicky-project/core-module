<?php
namespace Modules\Core\Services\Generators;

class UserModelParser
{
	private $content;
	private $tokens;

	public function __construct(string $content)
	{
		$this->content = $content;
		$this->tokens = token_get_all($content);
	}

	/**
	 * Mengecek apakah trait sudah ada di dalam class
	 */
	public function hasTrait(string $traitName): bool
	{
		$shortName = $this->extractShortName($traitName);
		$pattern = "/use\s+([\\\\\\w]+)?{$shortName}[,\\s;]/";
		return preg_match($pattern, $this->content) > 0;
	}

	/**
	 * Mengecek apakah use statement sudah ada di namespace
	 */
	public function hasNamespaceUse(string $traitName): bool
	{
		$pattern =
			"/^use\\s+" . preg_quote($traitName, "/") . "(\\s+as\\s+\\w+)?\\s*;/m";
		return preg_match($pattern, $this->content) > 0;
	}

	/**
	 * Mengekstrak semua trait yang digunakan di dalam class
	 */
	public function getUsedTraits(): array
	{
		$traits = [];
		$pattern = "/use\s+([^;]+);/";

		// Cari semua use statements di dalam class
		$lines = explode("\n", $this->content);
		$inClass = false;

		foreach ($lines as $line) {
			$trimmed = trim($line);

			if (strpos($trimmed, "class User") === 0) {
				$inClass = true;
				continue;
			}

			if (
				$inClass &&
				strpos($trimmed, "use ") === 0 &&
				strpos($trimmed, ";") !== false
			) {
				// Ekstrak nama trait
				$traitLine = trim(substr($trimmed, 3, -1));
				$traitNames = array_map("trim", explode(",", $traitLine));
				$traits = array_merge($traits, $traitNames);
			}

			// Hentikan pencarian jika menemukan property/method pertama
			if (
				$inClass &&
				(strpos($trimmed, 'protected $') === 0 ||
					strpos($trimmed, 'public $') === 0 ||
					strpos($trimmed, 'private $') === 0 ||
					strpos($trimmed, "function ") === 0)
			) {
				break;
			}
		}

		return $traits;
	}

	private function extractShortName(string $fqcn): string
	{
		$parts = explode("\\", $fqcn);
		return end($parts);
	}
}
