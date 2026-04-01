<?php

declare(strict_types=1);

namespace SeQura\Demo\Controllers;

use SeQura\Core\BusinessLogic\Domain\Connection\Services\CredentialsService;
use SeQura\Demo\Request;
use SeQura\Demo\Response;

/**
 * Serves page views (checkout and error).
 */
final readonly class PageController
{
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
        $merchantRef = $this->sanitizeIdentifier($request->getQueryParam('merchant_ref'));
        $assetsKey = $this->sanitizeIdentifier($request->getQueryParam('assets_key'));

        if ($merchantRef !== null && $merchantRef !== '' && $assetsKey !== null && $assetsKey !== '') {
            $_SESSION['tenant'] = [
                'merchant_ref' => $merchantRef,
                'assets_key'   => $assetsKey,
            ];
        } else {
            unset($_SESSION['tenant']);
        }

        if (!empty($_SESSION['tenant'])) {
            $resolvedAssetKey = $_SESSION['tenant']['assets_key'];
            $resolvedMerchantRef = $_SESSION['tenant']['merchant_ref'];
        } else {
            $allCredentials = $this->credentialsService->getCredentials();
            $credentials = !empty($allCredentials) ? $allCredentials[0] : null;
            $resolvedAssetKey = $credentials ? $credentials->getAssetsKey() : '';
            $resolvedMerchantRef = null;
        }

        return Response::view(
            'checkout',
            [
                'assetKey'    => $resolvedAssetKey,
                'merchantRef' => $resolvedMerchantRef,
            ]
        );
    }

    /**
     * Sanitize an identifier parameter (merchant_ref / assets_key).
     *
     * Trims whitespace and strips any character that is not alphanumeric,
     * a hyphen, an underscore, or a dot. Returns null when the input is null
     * or when the result after sanitization is empty.
     *
     * @param string|null $value Raw query parameter value.
     *
     * @return string|null
     */
    private function sanitizeIdentifier(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $sanitized = preg_replace('/[^a-zA-Z0-9_\-.]/', '', trim($value));

        return ($sanitized === '' || $sanitized === null) ? null : $sanitized;
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
