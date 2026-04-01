<?php

declare(strict_types=1);

namespace SeQura\Demo\Controllers;

/**
 * Shared input sanitization for identifier parameters (merchant_ref, assets_key, etc.).
 */
trait SanitizesIdentifiers
{
    /**
     * Sanitize an identifier parameter.
     *
     * Trims whitespace and strips any character that is not alphanumeric,
     * a hyphen, an underscore, or a dot. Returns null when the input is null
     * or when the result after sanitization is empty.
     *
     * @param string|null $value Raw parameter value.
     *
     * @return string|null
     */
    private static function sanitizeIdentifier(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $sanitized = preg_replace('/[^a-zA-Z0-9_\-.]/', '', trim($value));

        return ($sanitized === '' || $sanitized === null) ? null : $sanitized;
    }
}
