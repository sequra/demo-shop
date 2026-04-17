<?php

declare(strict_types=1);

namespace SeQura\Demo\Platform;

use SeQura\Demo\Config;
use SeQura\Demo\Request;

/**
 * Server-side Mixpanel tracking. Fire-and-forget via raw socket; never blocks page load.
 */
final class MixpanelService
{
    private const ENDPOINT_HOST = 'api-eu.mixpanel.com';
    private const ENDPOINT_PATH = '/track';
    private const BOT_PATTERN = '/bot|crawl|spider|slurp|bingpreview|curl|wget|headless/i';
    private const SOCKET_TIMEOUT = 5;
    private const FWRITE_SLEEP_US = 300000;

    private string $token;

    public function __construct()
    {
        $this->token = (string) Config::get('MIXPANEL_TOKEN', '');
    }

    public function trackPageView(Request $request): void
    {
        if ($this->token === '') {
            return;
        }

        $userAgent = $request->getHeader('user-agent') ?? '';
        if (preg_match(self::BOT_PATTERN, $userAgent) === 1) {
            return;
        }

        try {
            $referer = $request->getHeader('referer') ?? '';
            $origin = $this->classifyOrigin($referer);
            $refererHost = $referer !== '' ? (string) (parse_url($referer, PHP_URL_HOST) ?: '') : '';

            $properties = [
                'token'        => $this->token,
                'distinct_id'  => $this->generateUuid(),
                'time'         => time(),
                'origin'       => $origin,
                'referer_host' => $refererHost,
            ];

            $merchantRef = $request->getQueryParam('merchant_ref');
            if ($merchantRef !== null && $merchantRef !== '') {
                $properties['merchant_ref'] = $merchantRef;
            }

            $data = json_encode([['event' => 'Page Viewed', 'properties' => $properties]]);

            if ($data === false) {
                return;
            }

            $this->postAsync($data);
        } catch (\Throwable $e) {
            error_log('[MixpanelService] ' . $e->getMessage());
        }
    }

    private function classifyOrigin(string $referer): string
    {
        if ($referer === '') {
            return 'direct';
        }

        $host = strtolower((string) (parse_url($referer, PHP_URL_HOST) ?: ''));

        if ($host === '') {
            return 'direct';
        }

        if (str_contains($host, 'github.com') || str_contains($host, 'github.io')) {
            return 'github';
        }

        if ($host === 'portal.sequra.com') {
            return 'merchant_portal_prod';
        }

        if ($host === 'portal-sandbox.sequra.com') {
            return 'merchant_portal_sandbox';
        }

        if ($host === 'docs.sequra.com') {
            return 'docs';
        }

        return 'other';
    }

    private function generateUuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    private function postAsync(string $jsonData): void
    {
        $body = 'data=' . urlencode($jsonData);
        $payload = "POST " . self::ENDPOINT_PATH . " HTTP/1.1\r\n"
            . "Host: " . self::ENDPOINT_HOST . "\r\n"
            . "Content-Type: application/x-www-form-urlencoded\r\n"
            . "Content-Length: " . strlen($body) . "\r\n"
            . "Connection: close\r\n\r\n"
            . $body . "\r\n\r\n";

        // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
        $socket = @pfsockopen('tls://' . self::ENDPOINT_HOST, 443, $errCode, $errMsg, self::SOCKET_TIMEOUT);
        if ($socket === false) {
            error_log('[MixpanelService] Socket error ' . $errCode . ': ' . $errMsg);
            return;
        }

        fwrite($socket, $payload);
        usleep(self::FWRITE_SLEEP_US);
        fclose($socket);
    }
}
