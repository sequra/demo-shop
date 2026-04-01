<?php

use SeQura\Demo\Config;
use SeQura\Demo\Security\CsrfTokenManager;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(CsrfTokenManager::generateToken()) ?>">
    <title>SeQura Checkout</title>
    <link rel="icon" type="image/png" href="/dist/images/favicon/favicon.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f8f9fa;
            color: #333;
        }
    </style>
</head>
<body>
    <sequra-checkout
        asset-key="<?= htmlspecialchars($assetKey ?? '') ?>"
        <?php if (!empty($merchantRef)) : ?>merchant-ref="<?= htmlspecialchars($merchantRef) ?>"<?php endif; ?>
    ></sequra-checkout>
    <?php if (Config::get('APP_ENV', 'development') === 'development') : ?>
        <script type="module" src="http://localhost:3000/@vite/client"></script>
        <script type="module" src="http://localhost:3000/src/checkout-entry.js"></script>
    <?php else : ?>
        <script type="module" src="/dist/sequra-checkout.js"></script>
    <?php endif; ?>
</body>
</html>
