<?php

declare(strict_types=1);

namespace SeQura\Demo\Platform;

/**
 * Class MerchantContext.
 *
 * @package SeQura\Demo\Platform
 */
class MerchantContext
{
    public static function getMerchant(): ?MerchantDataDto
    {
        return !empty($_SESSION['merchant']) ? MerchantDataDto::fromArray($_SESSION['merchant']) : null;
    }

    public static function setMerchant(?MerchantDataDto $merchant): void
    {
        if (!$merchant) {
            unset($_SESSION['merchant']);
            return;
        }
        $_SESSION['merchant'] = $merchant->toArray();
    }
}
