# SeQura Integration Developer Guide

A step-by-step tutorial for integrating your e-commerce platform with SeQura using the `sequra/integration-core` PHP library. This guide walks through each piece you need to implement, using the demo shop code as a concrete reference.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Step 1: Install the Library](#step-1-install-the-library)
- [Step 2: Implement Core Infrastructure Services](#step-2-implement-core-infrastructure-services)
  - [2.1 Configuration Service](#21-configuration-service)
  - [2.2 Logger Service](#22-logger-service)
  - [2.3 Encryptor Service](#23-encryptor-service)
- [Step 3: Implement Platform Services](#step-3-implement-platform-services)
  - [3.1 Store Integration Service](#31-store-integration-service)
  - [3.2 Shop Order Statuses Service](#32-shop-order-statuses-service)
- [Step 4: Implement Order Management Services](#step-4-implement-order-management-services)
  - [4.1 Merchant Data Provider](#41-merchant-data-provider)
  - [4.2 Order Creation Service](#42-order-creation-service)
  - [4.3 Shop Order Service](#43-shop-order-service)
- [Step 5: Implement Repositories](#step-5-implement-repositories)
  - [5.1 Connection Data Repository](#51-connection-data-repository)
  - [5.2 Credentials Repository](#52-credentials-repository)
  - [5.3 Country Configuration Repository](#53-country-configuration-repository)
  - [5.4 Deployments Repository](#54-deployments-repository)
  - [5.5 SeQura Order Repository](#55-sequra-order-repository)
  - [5.6 Other Required Repositories](#56-other-required-repositories)
- [Step 6: Wire Everything in the Bootstrap](#step-6-wire-everything-in-the-bootstrap)
- [Step 7: Build the Checkout Flow](#step-7-build-the-checkout-flow)
  - [7.1 Create the Order Request Builder](#71-create-the-order-request-builder)
  - [7.2 Solicitation Endpoint](#72-solicitation-endpoint)
  - [7.3 Identification Form Endpoint](#73-identification-form-endpoint)
- [Step 8: Handle Webhooks (IPN)](#step-8-handle-webhooks-ipn)
  - [8.1 Webhook Endpoint](#81-webhook-endpoint)
  - [8.2 Webhook Validator](#82-webhook-validator)
  - [8.3 Webhook Handler](#83-webhook-handler)
- [Step 9: Integration Checklist](#step-9-integration-checklist)

---

## Prerequisites

- PHP >= 7.2 (8.x recommended)
- PHP extensions: `json`, `ctype`, `mbstring`
- Composer for dependency management
- SeQura merchant account with API credentials (`SEQURA_ACCOUNT_KEY`, `SEQURA_ACCOUNT_SECRET`)
- A publicly accessible URL for receiving webhooks (use ngrok or similar for local development)

## Step 1: Install the Library

```bash
composer require sequra/integration-core
```

## Step 2: Implement Core Infrastructure Services

These are the foundational services that the integration-core needs from your platform. Every integration **must** provide them.

### 2.1 Configuration Service

Extend `SeQura\Core\Infrastructure\Configuration\Configuration` to provide platform-specific configuration storage and retrieval.

Your implementation must provide:
- `getIntegrationName()` — a string identifying your platform (e.g. `"Magento2"`, `"WooCommerce"`)
- `getAsyncProcessUrl($guid)` — a URL for async task processing (return empty string if not using background tasks)
- `saveConfigValue($name, $value)` — persist a key-value pair
- `getConfigValue($name, $default)` — retrieve a stored value

**Demo reference** (`src/Platform/DemoConfiguration.php`):

```php
use SeQura\Core\Infrastructure\Configuration\Configuration;
use SeQura\Core\Infrastructure\Configuration\ConfigEntity;

class MyConfiguration extends Configuration
{
    protected static $instance;

    public function getIntegrationName(): string
    {
        return 'MyPlatform';
    }

    public function getAsyncProcessUrl($guid): string
    {
        // Return a URL that triggers async task processing in your platform
        return 'https://myshop.com/sequra/async?guid=' . $guid;
    }

    protected function saveConfigValue($name, $value): ConfigEntity
    {
        // Store in your database, config table, or platform-specific storage
        $entity = new ConfigEntity();
        $entity->setName($name);
        $entity->setValue($value);
        return $entity;
    }

    protected function getConfigValue($name, $default = null): mixed
    {
        // Read from your database or config storage
    }
}
```

### 2.2 Logger Service

Implement `SeQura\Core\Infrastructure\Logger\Interfaces\ShopLoggerAdapter` to bridge the core library's logging with your platform's logging system.

**Demo reference** (`src/Platform/DemoLoggerAdapter.php`):

```php
use SeQura\Core\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use SeQura\Core\Infrastructure\Logger\LogData;

class MyLogger implements ShopLoggerAdapter
{
    public function logMessage(LogData $data): void
    {
        // Route to your platform's logging system
        // $data->getLogLevel() returns 0=ERROR, 1=WARNING, 2=INFO, 3=DEBUG
        // $data->getMessage() returns the message text
        // $data->getContext() returns additional context data
    }
}
```

### 2.3 Encryptor Service

Implement `SeQura\Core\BusinessLogic\Utility\EncryptorInterface` to handle encryption/decryption of sensitive data (API credentials, tokens, etc.).

**Demo reference** (`src/Platform/DemoEncryptor.php`):

```php
use SeQura\Core\BusinessLogic\Utility\EncryptorInterface;

class MyEncryptor implements EncryptorInterface
{
    public function encrypt(string $data): string
    {
        // Use your platform's encryption mechanism
        // e.g. AES-256-CBC, or a platform-provided encryption service
    }

    public function decrypt(string $encryptedData): string
    {
        // Corresponding decryption
    }
}
```

## Step 3: Implement Platform Services

### 3.1 Store Integration Service

Implement `StoreIntegrationServiceInterface` to tell the core library about your store's webhook URL and supported capabilities.

**Demo reference** (`src/Platform/DemoStoreIntegration.php`):

```php
use SeQura\Core\BusinessLogic\Domain\Integration\StoreIntegration\StoreIntegrationServiceInterface;
use SeQura\Core\BusinessLogic\Domain\StoreIntegration\Models\Capability;
use SeQura\Core\BusinessLogic\Domain\URL\Model\URL;

class MyStoreIntegration implements StoreIntegrationServiceInterface
{
    public function getWebhookUrl(): URL
    {
        return new URL('https://myshop.com/sequra/webhook');
    }

    public function getSupportedCapabilities(): array
    {
        return [
            Capability::general(),
            Capability::widget(),
            Capability::orderStatus(),
        ];
    }
}
```

### 3.2 Shop Order Statuses Service

Implement `ShopOrderStatusesServiceInterface` to provide the list of order statuses in your platform. This is used by the admin UI to let merchants map SeQura states to platform states.

**Demo reference** (`src/Platform/DemoShopOrderStatuses.php`):

```php
use SeQura\Core\BusinessLogic\Domain\Integration\ShopOrderStatuses\ShopOrderStatusesServiceInterface;

class MyShopOrderStatuses implements ShopOrderStatusesServiceInterface
{
    public function getShopOrderStatuses(): array
    {
        // Return an array of your platform's order statuses
        // e.g. ['pending', 'processing', 'shipped', 'cancelled']
        return [];
    }
}
```

## Step 4: Implement Order Management Services

### 4.1 Merchant Data Provider

Implement `MerchantDataProviderInterface` to supply callback URLs and merchant options for the SeQura checkout flow. This tells SeQura where to redirect the customer or send notifications at each step.

**Demo reference** (`src/Platform/DemoMerchantDataProvider.php`):

```php
use SeQura\Core\BusinessLogic\Domain\Integration\Order\MerchantDataProviderInterface;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Options;

class MyMerchantDataProvider implements MerchantDataProviderInterface
{
    public function getNotifyUrl(): ?string
    {
        // The IPN webhook URL where SeQura will POST order status updates
        return 'https://myshop.com/sequra/ipn';
    }

    public function getApprovedCallback(): ?string
    {
        // JavaScript callback name invoked when payment is approved
        return '__sequraApproved';
    }

    public function getRejectedCallback(): ?string
    {
        // JavaScript callback name invoked when payment is rejected
        return '__sequraRejected';
    }

    public function getReturnUrlForCartId(string $cartId): ?string
    {
        // URL where the customer returns after completing the SeQura flow
        return 'https://myshop.com/checkout/confirmation';
    }

    public function getApprovedUrl(): ?string
    {
        return 'https://myshop.com/checkout/success';
    }

    public function getAbortUrl(): ?string
    {
        return 'https://myshop.com/checkout/cart';
    }

    public function getEditUrl(): ?string
    {
        return null;
    }

    public function getPartPaymentDetailsGetter(): ?string
    {
        return null;
    }

    public function getOptions(): ?Options
    {
        return new Options(false);
    }

    public function getEventsWebhookUrl(): string
    {
        return 'https://myshop.com/sequra/ipn';
    }

    public function getNotificationParametersForCartId(string $cartId): array
    {
        return [];
    }

    public function getEventsWebhookParametersForCartId(string $cartId): array
    {
        return [];
    }
}
```

### 4.2 Order Creation Service

Implement `OrderCreationInterface` to handle order creation in your platform when SeQura approves a payment.

**Demo reference** (`src/Platform/DemoOrderCreation.php`):

```php
use SeQura\Core\BusinessLogic\Domain\Integration\Order\OrderCreationInterface;

class MyOrderCreation implements OrderCreationInterface
{
    public function createOrder(string $cartId): string
    {
        // Convert the cart into a confirmed order in your platform
        // Return the platform's order ID
        $order = $this->orderService->createFromCart($cartId);
        return $order->getId();
    }
}
```

### 4.3 Shop Order Service

Implement `ShopOrderService` to handle order status updates triggered by SeQura webhooks and to provide order data for reporting.

**Demo reference** (`src/Platform/DemoShopOrderService.php`):

```php
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\CreateOrderRequest;
use SeQura\Core\BusinessLogic\Domain\Webhook\Models\Webhook;
use SeQura\Core\BusinessLogic\Webhook\Services\ShopOrderService;

class MyShopOrderService implements ShopOrderService
{
    public function updateStatus(
        Webhook $webhook,
        string $status,
        ?int $reasonCode = null,
        ?string $message = null,
    ): void {
        // Map SeQura state to your platform's status and update the order
        // $webhook->getSqState() is one of: 'approved', 'cancelled', 'needs_review'
        $order = $this->findOrderBySeQuraRef($webhook->getOrderRef());
        $order->setStatus($this->mapStatus($webhook->getSqState()));
        $order->save();
    }

    public function getCreateOrderRequest(string $orderReference): CreateOrderRequest
    {
        // Reconstruct the CreateOrderRequest for an existing order
        // Used by the webhook handler for order acknowledgement
    }

    public function getReportOrderIds(int $page, int $limit = 5000): array
    {
        // Return order IDs for delivery reporting to SeQura
        return [];
    }

    public function getStatisticsOrderIds(int $page, int $limit = 5000): array
    {
        // Return order IDs for statistical reporting
        return [];
    }

    public function getOrderUrl(string $merchantReference): string
    {
        // Return a URL to view the order in your admin panel
        return '';
    }
}
```

## Step 5: Implement Repositories

Repositories provide the data persistence layer. You must implement each repository contract using your platform's database or storage system.

### 5.1 Connection Data Repository

Implement `ConnectionDataRepositoryInterface` to store and retrieve SeQura API connection settings (environment, merchant ID, credentials).

**Demo reference** (`src/Repository/DemoConnectionDataRepository.php`):

```php
use SeQura\Core\BusinessLogic\Domain\Connection\RepositoryContracts\ConnectionDataRepositoryInterface;
use SeQura\Core\BusinessLogic\Domain\Connection\Models\ConnectionData;
use SeQura\Core\BusinessLogic\Domain\Connection\Models\AuthorizationCredentials;

class MyConnectionDataRepository implements ConnectionDataRepositoryInterface
{
    public function getConnectionDataByDeploymentId(string $deployment): ?ConnectionData { /* ... */ }
    public function setConnectionData(ConnectionData $connectionData): void { /* ... */ }
    public function getOldestConnectionSettingsStoreId(): ?string { /* ... */ }
    public function getAllConnectionSettingsStores(): array { /* ... */ }
    public function getAllConnectionSettings(): array { /* ... */ }
    public function deleteConnectionDataByDeploymentId(string $deploymentId): void { /* ... */ }
}
```

### 5.2 Credentials Repository

Implement `CredentialsRepositoryInterface` to manage per-country merchant credentials (merchant ID, assets key, etc.).

**Demo reference** (`src/Repository/DemoCredentialsRepository.php`):

```php
use SeQura\Core\BusinessLogic\Domain\Connection\RepositoryContracts\CredentialsRepositoryInterface;
use SeQura\Core\BusinessLogic\Domain\Connection\Models\Credentials;

class MyCredentialsRepository implements CredentialsRepositoryInterface
{
    public function setCredentials(array $credentials): void { /* ... */ }
    public function getCredentials(): array { /* ... */ }
    public function deleteCredentialsByDeploymentId(string $deploymentId): array { /* ... */ }
    public function getCredentialsByCountryCode(string $countryCode): ?Credentials { /* ... */ }
    public function getCredentialsByMerchantId(string $merchantId): ?Credentials { /* ... */ }
}
```

### 5.3 Country Configuration Repository

Implement `CountryConfigurationRepositoryInterface` to store which countries are configured with which merchant IDs.

**Demo reference** (`src/Repository/DemoCountryConfigRepository.php`):

```php
use SeQura\Core\BusinessLogic\Domain\CountryConfiguration\RepositoryContracts\CountryConfigurationRepositoryInterface;
use SeQura\Core\BusinessLogic\Domain\CountryConfiguration\Models\CountryConfiguration;

class MyCountryConfigRepository implements CountryConfigurationRepositoryInterface
{
    public function getCountryConfiguration(): ?array { /* ... */ }
    public function setCountryConfiguration(array $countryConfigurations): void { /* ... */ }
    public function deleteCountryConfigurations(): void { /* ... */ }
}
```

### 5.4 Deployments Repository

Implement `DeploymentsRepositoryInterface` to manage deployment (environment) data.

**Demo reference** (`src/Repository/DemoDeploymentsRepository.php`):

```php
use SeQura\Core\BusinessLogic\Domain\Deployments\RepositoryContracts\DeploymentsRepositoryInterface;
use SeQura\Core\BusinessLogic\Domain\Deployments\Models\Deployment;

class MyDeploymentsRepository implements DeploymentsRepositoryInterface
{
    public function getDeployments(): array { /* ... */ }
    public function getDeploymentById(string $deploymentId): ?Deployment { /* ... */ }
    public function setDeployments(array $deployments): void { /* ... */ }
    public function deleteDeployments(): void { /* ... */ }
}
```

### 5.5 SeQura Order Repository

Implement `SeQuraOrderRepositoryInterface` to store SeQura order data. This repository is critical for the checkout and webhook flows.

**Demo reference** (`src/Repository/DemoSeQuraOrderRepository.php`):

```php
use SeQura\Core\BusinessLogic\Domain\Order\RepositoryContracts\SeQuraOrderRepositoryInterface;
use SeQura\Core\BusinessLogic\Domain\Order\Models\SeQuraOrder;

class MySeQuraOrderRepository implements SeQuraOrderRepositoryInterface
{
    public function getByShopReference(string $shopOrderReference): ?SeQuraOrder { /* ... */ }
    public function getOrderBatchByShopReferences(array $shopOrderReferences): array { /* ... */ }
    public function getByCartId(string $cartId): ?SeQuraOrder { /* ... */ }
    public function getByOrderReference(string $sequraOrderReference): ?SeQuraOrder { /* ... */ }
    public function setSeQuraOrder(SeQuraOrder $order): void { /* ... */ }
    public function deleteOrder(SeQuraOrder $existingOrder): void { /* ... */ }
    public function deleteAllOrders(): void { /* ... */ }
}
```

### 5.6 Other Required Repositories

The following repositories are also required. If your integration does not use their features, you can provide no-op implementations:

- **`PaymentMethodRepositoryInterface`** — stores cached payment method data
- **`OrderStatusSettingsRepositoryInterface`** — stores order status mapping configuration
- **`StoreIntegrationRepositoryInterface`** — stores store integration settings

See `src/Bootstrap.php` for examples of no-op implementations using anonymous classes.

## Step 6: Wire Everything in the Bootstrap

The bootstrap class is where you register all your service implementations with the `ServiceRegister` and then initialize the core library. This is the most critical file in your integration.

The registration must happen in a specific order:

1. **Register platform services** — before calling `BootstrapComponent::init()`, because the core may reference them during initialization
2. **Call `BootstrapComponent::init()`** — registers all core services, controllers, proxies, and events
3. **Override registrations** — after core init, replace core repository and service registrations with your platform-specific implementations

**Demo reference** (`src/Bootstrap.php`):

```php
use SeQura\Core\BusinessLogic\BootstrapComponent;
use SeQura\Core\Infrastructure\Configuration\Configuration;
use SeQura\Core\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use SeQura\Core\Infrastructure\ServiceRegister;
use SeQura\Core\BusinessLogic\Utility\EncryptorInterface;
use SeQura\Core\BusinessLogic\Domain\Integration\Order\MerchantDataProviderInterface;
use SeQura\Core\BusinessLogic\Domain\Integration\Order\OrderCreationInterface;
use SeQura\Core\BusinessLogic\Domain\Integration\ShopOrderStatuses\ShopOrderStatusesServiceInterface;
use SeQura\Core\BusinessLogic\Domain\Integration\StoreIntegration\StoreIntegrationServiceInterface;
use SeQura\Core\BusinessLogic\Webhook\Services\ShopOrderService;
// ... other imports

final class Bootstrap
{
    public static function init(): void
    {
        // ---------------------------------------------------------------
        // 1. Register platform services BEFORE core init
        // ---------------------------------------------------------------

        ServiceRegister::registerService(
            Configuration::CLASS_NAME,
            static fn() => MyConfiguration::getInstance()
        );

        ServiceRegister::registerService(
            ShopLoggerAdapter::CLASS_NAME,
            static fn() => new MyLogger()
        );

        ServiceRegister::registerService(
            EncryptorInterface::class,
            static fn() => new MyEncryptor()
        );

        ServiceRegister::registerService(
            OrderCreationInterface::class,
            static fn() => new MyOrderCreation()
        );

        ServiceRegister::registerService(
            MerchantDataProviderInterface::class,
            static fn() => new MyMerchantDataProvider()
        );

        ServiceRegister::registerService(
            StoreIntegrationServiceInterface::class,
            static fn() => new MyStoreIntegration()
        );

        ServiceRegister::registerService(
            ShopOrderService::class,
            static fn() => new MyShopOrderService(
                ServiceRegister::getService(SeQuraOrderRepositoryInterface::class)
            )
        );

        ServiceRegister::registerService(
            ShopOrderStatusesServiceInterface::class,
            static fn() => new MyShopOrderStatuses()
        );

        // ---------------------------------------------------------------
        // 2. Initialize the core BootstrapComponent
        // ---------------------------------------------------------------

        BootstrapComponent::init();

        // ---------------------------------------------------------------
        // 3. Override repository and service registrations AFTER core init
        // ---------------------------------------------------------------

        ServiceRegister::registerService(
            ConnectionDataRepositoryInterface::class,
            static fn() => new MyConnectionDataRepository()
        );

        ServiceRegister::registerService(
            CredentialsRepositoryInterface::class,
            static fn() => new MyCredentialsRepository()
        );

        ServiceRegister::registerService(
            CountryConfigurationRepositoryInterface::class,
            static fn() => new MyCountryConfigRepository()
        );

        ServiceRegister::registerService(
            DeploymentsRepositoryInterface::class,
            static fn() => new MyDeploymentsRepository()
        );

        ServiceRegister::registerService(
            SeQuraOrderRepositoryInterface::class,
            static fn() => new MySeQuraOrderRepository()
        );

        // Register no-op repositories for features not yet used
        ServiceRegister::registerService(
            PaymentMethodRepositoryInterface::class,
            static fn() => new class implements PaymentMethodRepositoryInterface {
                public function getPaymentMethods(string $merchantId): array { return []; }
                public function setPaymentMethod(string $merchantId, SeQuraPaymentMethod $pm): void {}
                public function deletePaymentMethods(string $merchantId): void {}
                public function deleteAllPaymentMethods(): void {}
            }
        );

        // ... register WebhookValidator, WebhookHandler overrides as needed
    }
}
```

Call `Bootstrap::init()` early in your application's lifecycle — before handling any request that involves SeQura.

## Step 7: Build the Checkout Flow

The checkout flow has two main API interactions: **solicitation** (getting available payment methods) and **identification form** (rendering the SeQura payment form).

### 7.1 Create the Order Request Builder

Implement the `CreateOrderRequestBuilder` interface to transform your platform's cart/order data into a `CreateOrderRequest` that the core library understands.

**Demo reference** (`src/Builders/DemoCreateOrderRequestBuilder.php`):

```php
use SeQura\Core\BusinessLogic\Domain\Order\Builders\CreateOrderRequestBuilder;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\CreateOrderRequest;

class MyOrderRequestBuilder implements CreateOrderRequestBuilder
{
    private array $orderData;

    public function __construct(array $orderData)
    {
        $this->orderData = $orderData;
    }

    public function build(): CreateOrderRequest
    {
        // Transform your platform's cart/order data to a CreateOrderRequest
        // The data must include: cart items, delivery address, customer info,
        // delivery method, and totals
        return CreateOrderRequest::fromArray($this->orderData);
    }
}
```

### 7.2 Solicitation Endpoint

Create an endpoint that the frontend calls when the customer reaches the payment step. It uses `CheckoutAPI::get()->solicitation()` to ask SeQura which payment methods are available for this order.

**Demo reference** (`src/Controllers/CheckoutController.php` — `solicitation` method):

```php
use SeQura\Core\BusinessLogic\CheckoutAPI\CheckoutAPI;

// In your checkout controller:
public function solicitation(Request $request): Response
{
    $orderData = $request->getOrderData();
    $countryCode = $orderData['delivery_address']['country_code'] ?? 'ES';

    // Look up credentials for the customer's country
    $credentials = $this->credentialsService->getCredentialsByCountryCode($countryCode);
    if (!$credentials) {
        return $this->errorResponse("No credentials for country: {$countryCode}");
    }

    // Build the order request and solicit payment methods
    $builder = new MyOrderRequestBuilder($orderData);
    $response = CheckoutAPI::get()
        ->solicitation($storeId)
        ->solicitFor($builder);

    if (!$response->isSuccessful()) {
        return $this->errorResponse('Solicitation failed');
    }

    $result = $response->toArray();

    // Return available payment methods to the frontend
    return $this->jsonResponse([
        'paymentMethods' => $result['availablePaymentMethods'],
        'cartId' => $orderData['cart']['cart_ref'],
        'orderRef' => $result['order']['reference'] ?? '',
    ]);
}
```

### 7.3 Identification Form Endpoint

After the customer selects a SeQura payment method, fetch the identification form that SeQura provides for customer verification.

**Demo reference** (`src/Controllers/CheckoutController.php` — `getForm` method):

```php
use SeQura\Core\BusinessLogic\CheckoutAPI\CheckoutAPI;

public function getForm(Request $request): Response
{
    $cartId = $request->getQueryParam('cartId');
    $product = $request->getQueryParam('product');   // payment method code
    $campaign = $request->getQueryParam('campaign');  // optional campaign

    $formResponse = CheckoutAPI::get()
        ->solicitation($storeId)
        ->getIdentificationForm($cartId, $product, $campaign);

    if (!$formResponse->isSuccessful()) {
        return $this->errorResponse('Form could not be fetched');
    }

    // Return the HTML form to be rendered in an iframe
    return new Response(
        $formResponse->getIdentificationForm()->getForm(),
        200,
        ['Content-Type' => 'text/html']
    );
}
```

## Step 8: Handle Webhooks (IPN)

SeQura sends Instant Payment Notifications (IPN) to your server when order statuses change (approved, cancelled, needs_review). You must expose a publicly accessible endpoint for this.

### 8.1 Webhook Endpoint

Create a POST endpoint that receives webhook payloads from SeQura and passes them to the `WebhookAPI`.

**Demo reference** (`src/Controllers/WebhookController.php`):

```php
use SeQura\Core\BusinessLogic\WebhookAPI\WebhookAPI;

public function handleIpn(Request $request): Response
{
    $params = $request->getBody();

    // SeQura sends keys with an 'm_' prefix and uses 'event' instead of 'sq_state'
    $modifiedPayload = [];
    foreach ($params as $key => $value) {
        $newKey = $key === 'event' ? 'sq_state' : $this->trimPrefix($key, 'm_');
        $modifiedPayload[$newKey] = $value;
    }

    // storeId is required to set the correct store context
    if (empty($modifiedPayload['storeId'])) {
        return new Response('Missing storeId', 400);
    }

    $response = WebhookAPI::webhookHandler($modifiedPayload['storeId'])
        ->handleRequest($modifiedPayload);

    return new Response(
        json_encode($response->toArray()),
        $response->isSuccessful() ? 200 : 400
    );
}
```

**Important:** The webhook endpoint must be exempt from CSRF protection since SeQura sends server-to-server POST requests.

### 8.2 Webhook Validator

Extend `WebhookValidator` to resolve SeQura orders from your storage. The base class handles signature verification, state validation, and order existence checks.

**Demo reference** (`src/Webhook/DemoWebhookValidator.php`):

```php
use SeQura\Core\BusinessLogic\Domain\Order\Models\SeQuraOrder;
use SeQura\Core\BusinessLogic\Webhook\Validator\WebhookValidator;

class MyWebhookValidator extends WebhookValidator
{
    public function __construct(
        private readonly SeQuraOrderRepositoryInterface $orderRepository
    ) {}

    protected function getSeQuraOrderByOrderReference(string $orderRef): ?SeQuraOrder
    {
        return $this->orderRepository->getByOrderReference($orderRef);
    }
}
```

### 8.3 Webhook Handler

Extend `WebhookHandler` similarly to resolve orders for processing. The base class handles acknowledgement to SeQura and delegates status updates to your `ShopOrderService`.

**Demo reference** (`src/Webhook/DemoWebhookHandler.php`):

```php
use SeQura\Core\BusinessLogic\Webhook\Handler\WebhookHandler;

class MyWebhookHandler extends WebhookHandler
{
    public function __construct(
        ShopOrderService $shopOrderService,
        MerchantOrderRequestBuilder $merchantOrderRequestBuilder,
        private readonly SeQuraOrderRepositoryInterface $orderRepository,
    ) {
        parent::__construct($shopOrderService, $merchantOrderRequestBuilder);
    }

    protected function getSeQuraOrderByOrderReference(string $orderRef): ?SeQuraOrder
    {
        return $this->orderRepository->getByOrderReference($orderRef);
    }
}
```

Register both overrides in your Bootstrap (after `BootstrapComponent::init()`):

```php
ServiceRegister::registerService(
    WebhookValidator::class,
    static fn() => new MyWebhookValidator(
        ServiceRegister::getService(SeQuraOrderRepositoryInterface::class)
    )
);

ServiceRegister::registerService(
    WebhookHandler::class,
    static fn() => new MyWebhookHandler(
        ServiceRegister::getService(ShopOrderService::class),
        ServiceRegister::getService(MerchantOrderRequestBuilder::class),
        ServiceRegister::getService(SeQuraOrderRepositoryInterface::class)
    )
);
```

## Step 9: Integration Checklist

### Infrastructure Layer
- [ ] Configuration service extending `Configuration`
- [ ] Logger service implementing `ShopLoggerAdapter`
- [ ] Encryptor service implementing `EncryptorInterface`

### Platform Integration Layer
- [ ] Store integration service implementing `StoreIntegrationServiceInterface`
- [ ] Shop order statuses service implementing `ShopOrderStatusesServiceInterface`

### Order Management Layer
- [ ] Merchant data provider implementing `MerchantDataProviderInterface`
- [ ] Order creation service implementing `OrderCreationInterface`
- [ ] Shop order service implementing `ShopOrderService`

### Data Persistence Layer
- [ ] Connection data repository implementing `ConnectionDataRepositoryInterface`
- [ ] Credentials repository implementing `CredentialsRepositoryInterface`
- [ ] Country configuration repository implementing `CountryConfigurationRepositoryInterface`
- [ ] Deployments repository implementing `DeploymentsRepositoryInterface`
- [ ] SeQura order repository implementing `SeQuraOrderRepositoryInterface`
- [ ] Payment method repository implementing `PaymentMethodRepositoryInterface`
- [ ] Order status settings repository implementing `OrderStatusSettingsRepositoryInterface`
- [ ] Store integration repository implementing `StoreIntegrationRepositoryInterface`

### Bootstrap
- [ ] All services registered **before** `BootstrapComponent::init()`
- [ ] `BootstrapComponent::init()` called
- [ ] All repository overrides registered **after** `BootstrapComponent::init()`
- [ ] Webhook validator and handler overrides registered

### Checkout Flow
- [ ] `CreateOrderRequestBuilder` implemented for your cart/order data format
- [ ] Solicitation endpoint calling `CheckoutAPI::get()->solicitation()->solicitFor()`
- [ ] Identification form endpoint calling `CheckoutAPI::get()->solicitation()->getIdentificationForm()`

### Webhook (IPN) Flow
- [ ] Public POST endpoint for receiving webhooks
- [ ] Endpoint exempt from CSRF protection
- [ ] Payload transformation (SeQura `event` -> `sq_state`, remove `m_` prefix)
- [ ] Calls `WebhookAPI::webhookHandler($storeId)->handleRequest($payload)`
- [ ] Returns appropriate HTTP status codes (200 for success, 400/410 for errors)
