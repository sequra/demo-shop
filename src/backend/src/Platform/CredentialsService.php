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
     * Returns credentials by given county code
     *
     * @param string $countryCode
     *
     * @return Credentials|null
     */
    public function getCredentialsByCountryCode(string $countryCode): ?Credentials
    {
        $merchantId = $_SESSION['merchant_ref'] ?? Config::get('SEQURA_ACCOUNT_KEY', '');
        $credentials = $this->getCredentialsByMerchantId($merchantId);
        if ($credentials === null) {
            $this->refreshCredentials();
            $credentials = $this->getCredentialsByMerchantId($merchantId);
        }
        return $credentials;
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
