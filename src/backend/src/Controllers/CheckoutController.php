<?php

declare(strict_types=1);

namespace SeQura\Demo\Controllers;

use SeQura\Core\BusinessLogic\CheckoutAPI\CheckoutAPI;
use SeQura\Core\BusinessLogic\CheckoutAPI\PromotionalWidgets\Requests\PromotionalWidgetsCheckoutRequest;
use SeQura\Core\BusinessLogic\Domain\Connection\Services\CredentialsService;
use SeQura\Demo\Builders\DemoCreateOrderRequestBuilder;
use SeQura\Demo\Request;
use SeQura\Demo\Response;
use Throwable;

/**
 * Handles checkout solicitation and form retrieval via the SeQura CheckoutAPI.
 */
final readonly class CheckoutController
{
    private const string STORE_ID = 'demo';

    /**
     * @param CredentialsService $credentialsService
     */
    public function __construct(private CredentialsService $credentialsService)
    {
    }

    /**
     * Create a solicitation order via the CheckoutAPI.
     *
     * @param Request $request The incoming HTTP request.
     *
     * @return Response
     */
    public function solicitation(Request $request): Response
    {
        $payload = $request->getBody();
        $orderData = $payload['order'] ?? $payload;
        unset($orderData['merchant']);

        try {
            $countryCode = $orderData['delivery_address']['country_code'] ?? 'ES';

            $credentials = $this->credentialsService->getCredentialsByCountryCode($countryCode);

            if (!$credentials) {
                return Response::json(['error' => "No credentials for country: {$countryCode}"], 400);
            }

            if (empty($orderData['cart']['cart_ref'])) {
                $orderData['cart']['cart_ref'] = 'demo-' . uniqid('', true);
            }

            $builder = new DemoCreateOrderRequestBuilder($orderData);
            $response = CheckoutAPI::get()->solicitation(self::STORE_ID)->solicitFor($builder);

            if (!$response->isSuccessful()) {
                $errorData = method_exists($response, 'toArray') ? $response->toArray() : [];

                return Response::json(['error' => 'Solicitation failed', 'details' => $errorData], 400);
            }

            $responseArray = $response->toArray();

            $widgetRequest = new PromotionalWidgetsCheckoutRequest($countryCode, $countryCode);
            $widgetResponse = CheckoutAPI::get()->promotionalWidgets(self::STORE_ID)
                ->getPromotionalWidgetInitializeData($widgetRequest);
            $widgetData = $widgetResponse->toArray();

            return Response::json([
                'cartId' => $orderData['cart']['cart_ref'],
                'orderRef' => $responseArray['order']['reference'] ?? '',
                'paymentMethods' => $responseArray['availablePaymentMethods'],
                'merchantRef' => $credentials->getMerchantId(),
                'assetKey' => $credentials->getAssetsKey(),
                'scriptUri' => $widgetData['scriptUri'] ?? 'https://sandbox.sequracdn.com/assets/sequra-checkout.min.js',
            ]);
        } catch (Throwable $e) {
            return Response::json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Retrieve the identification form HTML for a given cart.
     *
     * @param Request $request The incoming HTTP request.
     *
     * @return Response
     */
    public function getForm(Request $request): Response
    {
        $cartId = $request->getQueryParam('cartId', '');
        $product = $request->getQueryParam('product');
        $campaign = $request->getQueryParam('campaign');

        if ($cartId === '') {
            return Response::json(['error' => 'cartId is required'], 400);
        }

        try {
            $formResponse = CheckoutAPI::get()->solicitation(self::STORE_ID)
                ->getIdentificationForm($cartId, $product, $campaign);

            if (!$formResponse->isSuccessful()) {
                return Response::json(['error' => 'Form could not be fetched'], 400);
            }

            return new Response(
                $formResponse->getIdentificationForm()->getForm(),
                200,
                ['Content-Type' => 'text/html']
            );
        } catch (Throwable $e) {
            return Response::json(['error' => $e->getMessage()], 500);
        }
    }
}
