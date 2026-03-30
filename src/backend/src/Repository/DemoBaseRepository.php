<?php

declare(strict_types=1);

namespace SeQura\Demo\Repository;

use Random\RandomException;
use RuntimeException;
use SeQura\Demo\Config;
use SeQura\Demo\Storage\EncryptionHelper;

/**
 * Abstract file-backed repository that reads/writes encrypted JSON data.
 *
 * Each concrete subclass specifies its own file name. The data directory
 * is resolved from the SEQURA_DATA_DIR env var (default: backend/data/).
 *
 * Uses a static cache keyed by class name so that multiple instances
 * (created by ServiceRegister) share the same in-memory data.
 */
abstract class DemoBaseRepository
{
    /** @var string $filePath */
    private readonly string $filePath;

    /** @var array<string, array<int|string, mixed>> class name => data */
    private static array $cache = [];

    /** @var array<string, bool> class name => loaded flag */
    private static array $loaded = [];

    /**
     * @param string $fileName The encrypted data file name.
     */
    public function __construct(string $fileName)
    {
        $dataDir = Config::get('SEQURA_DATA_DIR', __DIR__ . '/../../data');
        $this->filePath = rtrim($dataDir, '/\\') . '/' . $fileName;
    }

    /**
     * Read data from cache, loading from file on first access.
     *
     * @return array<int|string, mixed>
     */
    protected function getData(): array
    {
        $key = static::class;

        if (!isset(self::$loaded[$key])) {
            self::$cache[$key] = $this->readFromFile();
            self::$loaded[$key] = true;
        }

        return self::$cache[$key];
    }

    /**
     * Replace the in-memory data cache for this repository.
     *
     * @param array<int|string, mixed> $data The new data.
     *
     * @return void
     */
    protected function setData(array $data): void
    {
        $key = static::class;
        self::$cache[$key] = $data;
        self::$loaded[$key] = true;
    }

    /**
     * Encrypt and write current in-memory data to the file.
     *
     * @return void
     * @throws RandomException
     */
    public function writeToFile(): void
    {
        $dir = dirname($this->filePath);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $data = self::$cache[static::class] ?? [];
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $encrypted = EncryptionHelper::encrypt($json);

        file_put_contents($this->filePath, $encrypted, LOCK_EX);
    }

    /**
     * Read and decrypt data from the file.
     *
     * @return array<int|string, mixed>
     */
    private function readFromFile(): array
    {
        if (!file_exists($this->filePath)) {
            return [];
        }

        $contents = file_get_contents($this->filePath);

        if ($contents === false || $contents === '') {
            return [];
        }

        try {
            $json = EncryptionHelper::decrypt($contents);
        } catch (RuntimeException) {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }
}
