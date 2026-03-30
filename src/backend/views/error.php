<?php
/** @var string $errorMessage */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="/dist/images/favicon/favicon.png">
    <title>Error — SeQura Checkout</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f8f9fa;
            color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .error-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            padding: 48px;
            max-width: 520px;
            text-align: center;
        }
        .error-card h1 {
            font-size: 1.5rem;
            color: #dc3545;
            margin-bottom: 16px;
        }
        .error-card p {
            font-size: 1rem;
            color: #555;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .error-card code {
            display: block;
            background: #f1f3f5;
            border-radius: 6px;
            padding: 12px 16px;
            font-size: 0.85rem;
            color: #c92a2a;
            word-break: break-word;
            text-align: left;
        }
        .error-card a {
            display: inline-block;
            margin-top: 24px;
            padding: 10px 24px;
            background: #333;
            color: #fff;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .error-card a:hover { background: #555; }
    </style>
</head>
<body>
    <div class="error-card">
        <h1>Something went wrong</h1>
        <p>The application encountered an unexpected error.</p>
        <?php if (!empty($errorMessage)) : ?>
            <code><?= htmlspecialchars($errorMessage) ?></code>
        <?php endif; ?>
    </div>
</body>
</html>
