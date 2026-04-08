<?php

declare(strict_types=1);

namespace SeQura\Demo;

/**
 * Static configuration class that loads .env files and provides key-value access.
 */
final class Config
{
    /** @var array<string, string> */
    private static array $values = [];

    /**
     * Load configuration from a KEY=VALUE file.
     *
     * Skips empty lines and lines starting with #.
     * Handles quoted values (single and double quotes).
     *
     * @param string $filePath Absolute path to the .env file.
     */
    public static function load(string $filePath): void
    {
        if (!file_exists($filePath)) {
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines and comments
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            // Split on the first '=' only
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }

            $key = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));

            // Strip surrounding quotes (single or double)
            if (
                (strlen($value) >= 2)
                && (
                    ($value[0] === '"' && $value[strlen($value) - 1] === '"')
                    || ($value[0] === "'" && $value[strlen($value) - 1] === "'")
                )
            ) {
                $value = substr($value, 1, -1);
            }

            self::$values[$key] = $value;
        }
    }

    /**
     * Get a configuration value by key.
     *
     * @param string      $key     The configuration key.
     * @param string|null $default Default value if the key is not found.
     *
     * @return string|null
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        return self::$values[$key] ?? getenv($key) ?: $default;
    }

    /**
     * Reset all loaded configuration (useful for testing).
     */
    public static function reset(): void
    {
        self::$values = [];
    }
}
