<?php

declare(strict_types=1);

namespace SeQura\Demo\Controllers;

use SeQura\Core\BusinessLogic\Domain\Connection\Services\CredentialsService;
use SeQura\Demo\Platform\MerchantContext;
use SeQura\Demo\Platform\MerchantDataDto;
use SeQura\Demo\Request;
use SeQura\Demo\Response;

/**
 * Serves page views (checkout and error).
 */
final readonly class PageController
{
    use SanitizesIdentifiers;

    /**
     * @param CredentialsService $credentialsService
     */
    public function __construct(private CredentialsService $credentialsService)
    {
    }

    /**
     * Render the homepage (checkout view).
     *
     * When both `merchant_ref` and `assets_key` GET parameters are present,
     * stores them in the session as a tenant context and uses `assets_key` from
     * that context. If either parameter is absent, clears any existing tenant
     * context and falls back to the default credentials-based asset key.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function homepage(Request $request): Response
    {
        $this->setMerchantContextFromRequest($request);
        return Response::view('checkout', $this->resolveCredentials());
    }

    private function resolveCredentials(): array
    {
        $merchantData = MerchantContext::getMerchant();
        if ($merchantData !== null) {
            return [
                'assetKey' => $merchantData->getAssetsKey(),
                'merchantRef' => $merchantData->getMerchantId(),
            ];
        }
        $allCredentials = $this->credentialsService->getCredentials();
        $credentials = !empty($allCredentials) ? $allCredentials[0] : null;
        return [
            'assetKey' => $credentials ? $credentials->getAssetsKey() : '',
            'merchantRef' => null,
        ];
    }

    private function setMerchantContextFromRequest(Request $request): void
    {
        $merchantRef = self::sanitizeIdentifier($request->getQueryParam('merchant_ref'));
        $assetsKey = self::sanitizeIdentifier($request->getQueryParam('assets_key'));
        $merchantDto = $merchantRef && $assetsKey ? new MerchantDataDto($merchantRef, $assetsKey) : null;
        MerchantContext::setMerchant($merchantDto);
    }

    /**
     * Render the error page.
     *
     * @param string $errorMessage The error message to display.
     *
     * @return Response
     */
    public function errorPage(string $errorMessage = ''): Response
    {
        return Response::view('error', ['errorMessage' => $errorMessage], 500);
    }
}
