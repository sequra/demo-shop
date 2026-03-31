<?php

declare(strict_types=1);

namespace SeQura\Demo\Platform;

use SeQura\Core\BusinessLogic\Utility\EncryptorInterface;
use SeQura\Demo\Storage\EncryptionHelper;

/**
 * AES-256-CBC encryption adapter for integration-core.
 *
 * Delegates to EncryptionHelper which reads the key from SEQURA_ENCRYPTION_KEY env var.
 */
final class DemoEncryptor implements EncryptorInterface
{
    /**
     * @inheritDoc
     */
    public function encrypt(string $data): string
    {
        return EncryptionHelper::encrypt($data);
    }

    /**
     * @inheritDoc
     */
    public function decrypt(string $encryptedData): string
    {
        return EncryptionHelper::decrypt($encryptedData);
    }
}
