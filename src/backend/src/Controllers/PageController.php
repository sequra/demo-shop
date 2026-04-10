<?php

declare(strict_types=1);

namespace SeQura\Demo\Controllers;

use SeQura\Core\BusinessLogic\Domain\Connection\Services\CredentialsService;
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
     * @return Response
     */
    public function homepage(): Response
    {
        if($_REQUEST['merchant_ref'] ?? false) {
            $_SESSION['merchant_ref'] = preg_replace(
                '/[^a-zA-Z0-9_\-.]/', '', trim($_REQUEST['merchant_ref'])
            );
        }

        $allCredentials = $this->credentialsService->getCredentials();
        $credentials = !empty($allCredentials) ? $allCredentials[0] : null;

        return Response::view(
            'checkout',
            [
                'assetKey' => $credentials ? $credentials->getAssetsKey() : '',
            ]
        );
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
