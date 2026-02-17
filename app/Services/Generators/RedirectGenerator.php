<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\File;

class RedirectEditor
{
	protected string $filePath;
	protected string $backupDir;

	public function __construct()
	{
		$this->filePath = app_path("Http/Controllers/Auth/LoginController.php");
		$this->backupDir = storage_path("app/backup/login-controller");
	}

	public function setRedirectTo(string $newUrl): bool
	{
		if (!File::exists($this->filePath)) {
			throw new \Exception("File LoginController tidak ditemukan.");
		}

		$content = File::get($this->filePath);
		$this->backup($content);

		// Regex untuk mencari protected $redirectTo = '...';
		$pattern = '/protected\s+\$redirectTo\s*=\s*([\'"])(.*?)\1\s*;/';
		$replacement = "protected \$redirectTo = '$newUrl';";

		$newContent = preg_replace($pattern, $replacement, $content, -1, $count);

		if ($count === 0) {
			// Coba pola lebih longgar
			$pattern = '/protected\s+\$redirectTo\s*=\s*(.*?);/';
			$newContent = preg_replace(
				$pattern,
				"protected \$redirectTo = '$newUrl';",
				$content,
				-1,
				$count
			);
		}

		if ($count === 0) {
			throw new \Exception("Tidak dapat menemukan properti \$redirectTo.");
		}

		File::put($this->filePath, $newContent);
		return true;
	}

	public function restore(): bool
	{
		$backupFiles = File::glob($this->backupDir . "/*.bak");

		if (empty($backupFiles)) {
			throw new \Exception("Tidak ada backup ditemukan.");
		}

		$latestBackup = collect($backupFiles)
			->sortByDesc(function ($file) {
				return File::lastModified($file);
			})
			->first();

		$backupContent = File::get($latestBackup);
		File::put($this->filePath, $backupContent);

		return true;
	}

	protected function backup(string $content): void
	{
		if (!File::exists($this->backupDir)) {
			File::makeDirectory($this->backupDir, 0755, true);
		}

		$backupFile = $this->backupDir . "/" . date("Y-m-d_His") . ".bak";
		File::put($backupFile, $content);
	}
}
