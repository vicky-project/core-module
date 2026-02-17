<?php

namespace Modules\Core\Console;

use Illuminate\Console\Command;
use Modules\Core\Services\Generators\RedirectEditor;

class ModifyRedirectCommand extends Command
{
	/**
	 * Signature command: menerima argument url opsional dan opsi --restore.
	 *
	 * @var string
	 */
	protected $signature = 'app:modify 
                            {url? : Tujuan redirect setelah login (tidak diperlukan jika menggunakan --restore)}
                            {--restore : Mengembalikan redirectTo ke backup terakhir}';

	/**
	 * Deskripsi command.
	 *
	 * @var string
	 */
	protected $description = "Mengubah atau mengembalikan nilai redirectTo di LoginController";

	/**
	 * @param \App\Services\RedirectEditor $editor
	 */
	public function __construct(private RedirectEditor $editor)
	{
		parent::__construct();
	}

	/**
	 * Eksekusi command.
	 *
	 * @return int
	 */
	public function handle(): int
	{
		// Jika opsi --restore diberikan, lakukan restore
		if ($this->option("restore")) {
			return $this->restore();
		}

		// Jika tidak, pastikan argument url diberikan
		$url = $this->argument("url");
		if (!$url) {
			$this->error(
				"Argument url wajib diisi jika tidak menggunakan --restore."
			);
			return 1;
		}

		return $this->setRedirect($url);
	}

	/**
	 * Mengubah redirectTo ke URL baru.
	 *
	 * @param string $url
	 * @return int
	 */
	protected function setRedirect(string $url): int
	{
		try {
			$this->editor->setRedirectTo($url);
			$this->info("RedirectTo berhasil diubah menjadi '$url'.");
			return 0;
		} catch (\Exception $e) {
			$this->error("Gagal mengubah: " . $e->getMessage());
			return 1;
		}
	}

	/**
	 * Mengembalikan redirectTo dari backup terakhir.
	 *
	 * @return int
	 */
	protected function restore(): int
	{
		try {
			$this->editor->restore();
			$this->info("RedirectTo berhasil dikembalikan ke backup terakhir.");
			return 0;
		} catch (\Exception $e) {
			$this->error("Gagal restore: " . $e->getMessage());
			return 1;
		}
	}
}
