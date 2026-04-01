<?php

declare(strict_types=1);

namespace SeQura\Demo\Controllers;

use SeQura\Core\BusinessLogic\WebhookAPI\WebhookAPI;
use SeQura\Demo\Request;
use SeQura\Demo\Response;

/**
 * Handles incoming IPN (Instant Payment Notification) webhooks from SeQura.
 */
final class WebhookController
{
    private const string PREFIX = 'm_';
    private const string STORE_ID = 'demo';

    /**
     * Process an IPN webhook request.
     *
     * @param Request $request The incoming HTTP request.
     *
     * @return Response
     */
    public function handleIpn(Request $request): Response
    {
        $params = $request->getBody();

        $modifiedPayload = [];
        foreach ($params as $key => $value) {
            $newKey = $key === 'event' ? 'sq_state' : $this->trimPrefixFromKey($key);
            $modifiedPayload[$newKey] = $value;
        }

        if (!empty($modifiedPayload['merchant_ref'])) {
            $storeId = self::STORE_ID;
        } elseif (!empty($modifiedPayload['storeId'])) {
            $storeId = $modifiedPayload['storeId'];
        } else {
            return new Response('Missing storeId', 400);
        }

        $response = WebhookAPI::webhookHandler($storeId)->handleRequest($modifiedPayload);

        return new Response(json_encode($response->toArray()), $response->isSuccessful() ? 200 : 400);
    }

    /**
     * Remove the merchant prefix from a webhook payload key.
     *
     * @param string $key The original key.
     *
     * @return string
     */
    private function trimPrefixFromKey(string $key): string
    {
        return str_starts_with($key, self::PREFIX) ? substr($key, strlen(self::PREFIX)) : $key;
    }
}
