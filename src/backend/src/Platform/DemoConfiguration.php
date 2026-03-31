<?php

declare(strict_types=1);

namespace SeQura\Demo\Platform;

use SeQura\Core\Infrastructure\Configuration\ConfigEntity;
use SeQura\Core\Infrastructure\Configuration\Configuration;

/**
 * In-memory configuration for the SeQura Demo.
 *
 * Bypasses ConfigurationManager entirely: values live in a plain
 * PHP array and are lost when the process ends.  This is by design
 * because the demo has no persistent database.
 */
class DemoConfiguration extends Configuration
{
    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;

    /**
     * In-memory key/value store.
     *
     * @var array<string, mixed>
     */
    private array $configStore = [];

    /**
     * @inheritDoc
     */
    public function getIntegrationName(): string
    {
        return 'SeQura Demo';
    }

    /**
     * @inheritDoc
     */
    public function getAsyncProcessUrl($guid): string
    {
        return '';
    }

    /**
     * Persist a configuration value in memory and return a ConfigEntity.
     *
     * @param string $name  Configuration key.
     * @param mixed  $value Configuration value.
     *
     * @return ConfigEntity
     */
    protected function saveConfigValue($name, $value): ConfigEntity
    {
        $this->configStore[$name] = $value;

        $entity = new ConfigEntity();
        $entity->setId(1);
        $entity->setName($name);
        $entity->setValue($value);

        return $entity;
    }

    /**
     * Read a configuration value from memory.
     *
     * @param string $name    Configuration key.
     * @param mixed  $default Fallback when the key has not been stored.
     *
     * @return mixed
     */
    protected function getConfigValue($name, $default = null): mixed
    {
        return array_key_exists($name, $this->configStore)
            ? $this->configStore[$name]
            : $default;
    }
}
