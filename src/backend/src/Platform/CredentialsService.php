<?php

declare(strict_types=1);

namespace SeQura\Demo\Platform;

use SeQura\Core\BusinessLogic\Domain\Connection\Models\Credentials;
use SeQura\Core\BusinessLogic\Domain\Connection\Services\CredentialsService as BaseCredentialsService;

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
        $merchantId = MerchantContext::getMerchant()?->getMerchantId();
        if ($merchantId) {
            // Force using the merchant ID received from the context to fetch credentials.
            return $this->getCredentialsByMerchantId($merchantId);
        }
        // Fallback to default behavior if no merchant is set in the context.
        return parent::getCredentialsByCountryCode($countryCode);
    }
}
