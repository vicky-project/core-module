<?php
namespace Modules\Core\Services\Generators;

class TraitInserter
{
	/**
	 * Menyisipkan trait ke dalam User model
	 *
	 * @param string $traitName Nama trait (dengan namespace lengkap)
	 * @param string $traitAlias Alias untuk trait (opsional)
	 * @param string $modelPath Path ke file User model
	 * @return array Hasil operasi
	 */
	public static function insertTrait(
		string $traitName,
		string $traitAlias = null,
		string $modelPath = null
	): array {
		try {
			// Tentukan path default jika tidak diberikan
			if ($modelPath === null) {
				$modelPath = base_path("app/Models/User.php");
			}

			// Validasi file
			if (!file_exists($modelPath)) {
				throw new Exception("File User model tidak ditemukan di: $modelPath");
			}

			// Baca konten file
			$content = file_get_contents($modelPath);

			// Parse konten
			$parser = new UserModelParser($content);

			// Cek apakah trait sudah ada
			if ($parser->hasTrait($traitName)) {
				return [
					"success" => false,
					"message" => "Trait '$traitName' sudah ada dalam model",
					"file" => $modelPath,
				];
			}

			// Tambahkan use statement untuk trait di namespace jika belum ada
			$content = self::addNamespaceUse($content, $traitName, $traitAlias);

			// Tambahkan trait ke dalam class
			$content = self::addTraitToClass($content, $traitName, $traitAlias);

			// Simpan file
			if (file_put_contents($modelPath, $content) === false) {
				throw new Exception("Gagal menyimpan file: $modelPath");
			}

			return [
				"success" => true,
				"message" => "Trait '$traitName' berhasil ditambahkan ke User model",
				"file" => $modelPath,
				"backup" => self::createBackup($modelPath),
			];
		} catch (Exception $e) {
			return [
				"success" => false,
				"message" => $e->getMessage(),
				"file" => $modelPath ?? "unknown",
			];
		}
	}

	/**
	 * Menambahkan use statement untuk trait di bagian namespace
	 */
	private static function addNamespaceUse(
		string $content,
		string $traitName,
		?string $alias = null
	): string {
		$parser = new UserModelParser($content);

		// Jika use statement sudah ada, kembalikan konten asli
		if ($parser->hasNamespaceUse($traitName)) {
			return $content;
		}

		// Ekstrak nama trait tanpa namespace
		$traitShortName = self::extractShortName($traitName);

		// Buang namespace jika nama pendek sama dengan alias
		if ($alias && $alias === $traitShortName) {
			$traitName = $traitShortName;
		}

		// Buat use statement
		$useStatement = "use $traitName";
		if ($alias) {
			$useStatement .= " as $alias";
		}
		$useStatement .= ";\n";

		// Temukan posisi untuk menyisipkan use statement
		// Biasanya setelah namespace dan sebelum class
		$lines = explode("\n", $content);
		$newLines = [];
		$inserted = false;
		$afterNamespace = false;

		foreach ($lines as $line) {
			$newLines[] = $line;

			// Setelah baris namespace, sisipkan use statement
			if (!$inserted && strpos(trim($line), "namespace ") === 0) {
				$afterNamespace = true;
			}

			// Sisipkan setelah baris use terakhir atau setelah namespace jika tidak ada use
			if ($afterNamespace && !$inserted) {
				$trimmedLine = trim($line);
				if (
					empty($trimmedLine) ||
					(strpos($trimmedLine, "use ") !== 0 &&
						strpos($trimmedLine, "class ") === 0)
				) {
					// Sisipkan sebelum class
					array_splice($newLines, -1, 0, $useStatement);
					$inserted = true;
				}
			}
		}

		// Jika belum disisipkan, tambahkan di akhir sebelum class
		if (!$inserted) {
			// Cari baris dengan class
			$classLineIndex = null;
			foreach ($newLines as $index => $line) {
				if (strpos(trim($line), "class User") === 0) {
					$classLineIndex = $index;
					break;
				}
			}

			if ($classLineIndex !== null) {
				array_splice($newLines, $classLineIndex, 0, $useStatement);
			}
		}

		return implode("\n", $newLines);
	}

	/**
	 * Menambahkan trait ke dalam class definition
	 */
	private static function addTraitToClass(
		string $content,
		string $traitName,
		?string $alias = null
	): string {
		$parser = new UserModelParser($content);

		// Gunakan alias jika ada, jika tidak gunakan short name
		$traitToUse = $alias ?: self::extractShortName($traitName);

		// Cari baris dengan use traits di dalam class
		$lines = explode("\n", $content);
		$newLines = [];
		$classFound = false;
		$traitLineFound = false;
		$inserted = false;

		foreach ($lines as $i => $line) {
			$newLines[] = $line;

			// Tandai ketika menemukan class User
			if (!$classFound && strpos(trim($line), "class User") === 0) {
				$classFound = true;
				continue;
			}

			// Setelah menemukan class, cari baris trait use
			if ($classFound && !$inserted) {
				$trimmedLine = trim($line);

				// Jika menemukan baris dengan "use" untuk trait di dalam class
				if (
					strpos($trimmedLine, "use ") === 0 &&
					strpos($trimmedLine, ";") !== false
				) {
					$traitLineFound = true;

					// Periksa apakah ini adalah baris trait terakhir
					$nextLine = isset($lines[$i + 1]) ? trim($lines[$i + 1]) : "";

					// Jika baris berikutnya bukan trait use, tambahkan di sini
					if (strpos($nextLine, "use ") !== 0) {
						// Modifikasi baris saat ini untuk menambahkan trait
						$line = rtrim($line, ";");
						if (substr($line, -1) === ",") {
							$line .= " $traitToUse;";
						} else {
							$line .= ", $traitToUse;";
						}
						$newLines[count($newLines) - 1] = $line;
						$inserted = true;
					}
				}
				// Jika menemukan property atau method pertama setelah trait use
				elseif (
					$traitLineFound &&
					!$inserted &&
					(strpos($trimmedLine, 'protected $') === 0 ||
						strpos($trimmedLine, 'public $') === 0 ||
						strpos($trimmedLine, 'private $') === 0 ||
						strpos($trimmedLine, "function ") === 0 ||
						strpos($trimmedLine, "/**") === 0)
				) {
					// Sisipkan baris trait sebelum property/method ini
					$insertLine = "    use $traitToUse;\n";
					array_splice($newLines, -1, 0, $insertLine);
					$inserted = true;
				}
			}
		}

		// Jika belum disisipkan, tambahkan setelah kurung buka class
		if (!$inserted && $classFound) {
			// Cari baris dengan { setelah class
			foreach ($newLines as $index => $line) {
				if (strpos(trim($line), "class User") === 0) {
					// Cari kurung buka
					for ($j = $index; $j < count($newLines); $j++) {
						if (strpos($newLines[$j], "{") !== false) {
							// Sisipkan setelah kurung buka
							$insertLine = "\n    use $traitToUse;";
							array_splice($newLines, $j + 1, 0, $insertLine);
							$inserted = true;
							break;
						}
					}
					break;
				}
			}
		}

		return implode("\n", $newLines);
	}

	/**
	 * Mengekstrak nama pendek dari FQCN
	 */
	private static function extractShortName(string $fqcn): string
	{
		$parts = explode("\\", $fqcn);
		return end($parts);
	}

	/**
	 * Membuat backup file
	 */
	private static function createBackup(string $filePath): string
	{
		$backupPath = $filePath . ".backup." . date("Ymd_His");
		if (copy($filePath, $backupPath)) {
			return $backupPath;
		}
		return "";
	}
}
