<?php

declare(strict_types=1);

namespace SeQura\Demo\Controllers;

use SeQura\Core\BusinessLogic\CheckoutAPI\CheckoutAPI;
use SeQura\Core\BusinessLogic\CheckoutAPI\PromotionalWidgets\Requests\PromotionalWidgetsCheckoutRequest;
use SeQura\Core\BusinessLogic\Domain\Connection\Services\CredentialsService;
use SeQura\Core\BusinessLogic\Domain\Multistore\StoreContext;
use SeQura\Core\BusinessLogic\Domain\Order\Builders\MerchantOrderRequestBuilder;
use SeQura\Demo\Builders\DemoCreateOrderRequestBuilder;
use SeQura\Demo\Platform\MerchantContext;
use SeQura\Demo\Platform\MerchantDataDto;
use SeQura\Demo\Repository\DemoSeQuraOrderRepository;
use SeQura\Demo\Request;
use SeQura\Demo\Response;
use Throwable;

/**
 * Handles checkout solicitation and form retrieval via the SeQura CheckoutAPI.
 */
final readonly class CheckoutController
{
    use SanitizesIdentifiers;
    private const string STORE_ID = 'demo';

    /**
     * @param CredentialsService $credentialsService
     * @param DemoSeQuraOrderRepository $orderRepository
     * @param MerchantOrderRequestBuilder $merchantOrderRequestBuilder
     */
    public function __construct(
        private CredentialsService $credentialsService,
        private DemoSeQuraOrderRepository $orderRepository,
        private MerchantOrderRequestBuilder $merchantOrderRequestBuilder
    ) {
    }

    /**
     * Create a solicitation order via the CheckoutAPI.
     *
     * When a tenant context is present in the session, the cart is associated
     * with the merchant reference before the solicitation call so that
     * getNotificationParametersForCartId() can embed it in the order payload.
     * The response then carries the tenant's merchant_ref and assets_key.
     *
     * Without a tenant context the existing country-code credential lookup is
     * used unchanged.
     *
     * @param Request $request The incoming HTTP request.
     *
     * @return Response
     */
    public function solicitation(Request $request): Response
    {
        $payload = $request->getBody();
        $orderData = $payload['order'] ?? $payload;
        if (empty($orderData['cart']['cart_ref'])) {
            $orderData['cart']['cart_ref'] = 'demo-' . uniqid('', true);
        }
        $cartId = $orderData['cart']['cart_ref'];
        $countryCode = $orderData['delivery_address']['country_code'] ?? 'ES';

        $merchantData = MerchantContext::getMerchant();
        // Fall back to payload values when the session tenant is absent (e.g. the
        // session was cleared by another tab navigating without the tenant params).
        if ($merchantData === null && !empty($payload['merchant_ref']) && !empty($payload['assets_key'])) {
            $merchantRef = self::sanitizeIdentifier($payload['merchant_ref']);
            $assetsKey = self::sanitizeIdentifier($payload['assets_key']);
            if ($merchantRef && $assetsKey) {
                $merchantData = new MerchantDataDto($merchantRef, $assetsKey);
                MerchantContext::setMerchant($merchantData);
            }
        }

        try {
            if ($merchantData !== null) {
                // Store cart→merchant mapping BEFORE the solicitation call so
                // that getNotificationParametersForCartId() returns it in time.
                $this->orderRepository->setMerchantContext($cartId, $merchantData->getMerchantId());
            } else {
                $credentials = $this->credentialsService->getCredentialsByCountryCode($countryCode);

                if (!$credentials) {
                    return Response::json(['error' => "No credentials for country: {$countryCode}"], 400);
                }
                $merchantData = new MerchantDataDto($credentials->getMerchantId(), $credentials->getAssetsKey());
            }

            $orderData['merchant'] = StoreContext::doWithStore(self::STORE_ID, function () use ($countryCode, $cartId) {
                return $this->merchantOrderRequestBuilder->build($countryCode, $cartId)->toArray();
            });

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
                'cartId' => $cartId,
                'orderRef' => $responseArray['order']['reference'] ?? '',
                'paymentMethods' => $responseArray['availablePaymentMethods'],
                'merchantRef' => $merchantData->getMerchantId(),
                'assetKey' => $merchantData->getAssetsKey(),
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