<?php

declare(strict_types=1);

namespace SeQura\Demo\Platform;

use SeQura\Core\BusinessLogic\Domain\Connection\Models\Credentials;
use SeQura\Core\BusinessLogic\Domain\Connection\Services\CredentialsService as BaseCredentialsService;
use SeQura\Demo\Console\InitDataCommand;
use SeQura\Demo\Config;

/**
 * Class CredentialsService.
 *
 * @package SeQura\Demo\Platform
 */
class CredentialsService extends BaseCredentialsService
{
    /**
     * Resolves credentials for the current session merchant_ref (falling back to the
     * account key). The $countryCode is required by the overridden contract but is
     * intentionally ignored — the demo scopes every request to a single merchant.
     *
     * @param string $countryCode Ignored; kept for the overridden signature.
     *
     * @return Credentials|null
     */
    public function getCredentialsByCountryCode(string $countryCode): ?Credentials
    {
        $merchantId = self::currentMerchantId();
        $credentials = $this->getCredentialsByMerchantId($merchantId);
        if ($credentials === null) {
            $this->refreshCredentials();
            $credentials = $this->getCredentialsByMerchantId($merchantId);
        }
        return $credentials;
    }

    /**
     * Current merchant: the session merchant_ref, falling back to the configured account key.
     */
    public static function currentMerchantId(): string
    {
        return $_SESSION['merchant_ref'] ?? Config::get('SEQURA_ACCOUNT_KEY', '');
    }

    private function refreshCredentials(): void
    {
        $command = new InitDataCommand();
        ob_start();
        try {
            $command->execute();
        } finally {
            ob_end_clean();
        }
    }
}
