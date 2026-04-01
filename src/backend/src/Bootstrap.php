<?php

declare(strict_types=1);

namespace SeQura\Demo;

use SeQura\Core\BusinessLogic\BootstrapComponent;
use SeQura\Core\BusinessLogic\Domain\Connection\ProxyContracts\ConnectionProxyInterface;
use SeQura\Core\Infrastructure\Configuration\Configuration;
use SeQura\Core\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use SeQura\Core\Infrastructure\ServiceRegister;
use SeQura\Core\BusinessLogic\Domain\Integration\Order\MerchantDataProviderInterface;
use SeQura\Core\BusinessLogic\Domain\Integration\Order\OrderCreationInterface;
use SeQura\Core\BusinessLogic\Domain\Integration\ShopOrderStatuses\ShopOrderStatusesServiceInterface;
use SeQura\Core\BusinessLogic\Domain\Integration\StoreIntegration\StoreIntegrationServiceInterface;
use SeQura\Core\BusinessLogic\Utility\EncryptorInterface;
use SeQura\Core\BusinessLogic\Webhook\Services\ShopOrderService;
use SeQura\Core\BusinessLogic\Domain\Connection\RepositoryContracts\ConnectionDataRepositoryInterface;
use SeQura\Core\BusinessLogic\Domain\Connection\RepositoryContracts\CredentialsRepositoryInterface;
use SeQura\Core\BusinessLogic\Domain\CountryConfiguration\RepositoryContracts\CountryConfigurationRepositoryInterface;
use SeQura\Core\BusinessLogic\Domain\Deployments\RepositoryContracts\DeploymentsRepositoryInterface;
use SeQura\Core\BusinessLogic\Domain\Order\Builders\MerchantOrderRequestBuilder;
use SeQura\Core\BusinessLogic\Domain\Order\RepositoryContracts\SeQuraOrderRepositoryInterface;
use SeQura\Core\BusinessLogic\Domain\OrderStatusSettings\RepositoryContracts\OrderStatusSettingsRepositoryInterface;
use SeQura\Core\BusinessLogic\Domain\PaymentMethod\Models\SeQuraPaymentMethod;
use SeQura\Core\BusinessLogic\Domain\PaymentMethod\RepositoryContracts\PaymentMethodRepositoryInterface;
use SeQura\Core\BusinessLogic\Domain\StoreIntegration\Models\StoreIntegration;
use SeQura\Core\BusinessLogic\Domain\StoreIntegration\RepositoryContracts\StoreIntegrationRepositoryInterface;
use SeQura\Core\BusinessLogic\Webhook\Handler\WebhookHandler;
use SeQura\Core\BusinessLogic\Webhook\Validator\WebhookValidator;
use SeQura\Demo\Platform\DemoConfiguration;
use SeQura\Demo\Platform\DemoEncryptor;
use SeQura\Demo\Platform\DemoLoggerAdapter;
use SeQura\Demo\Platform\DemoMerchantDataProvider;
use SeQura\Demo\Platform\DemoOrderCreation;
use SeQura\Demo\Platform\DemoShopOrderService;
use SeQura\Demo\Platform\DemoShopOrderStatuses;
use SeQura\Demo\Platform\DemoStoreIntegration;
use SeQura\Core\BusinessLogic\Domain\Deployments\ProxyContracts\DeploymentsProxyInterface;
use SeQura\Core\BusinessLogic\Domain\Deployments\Services\DeploymentsService;
use SeQura\Core\BusinessLogic\Domain\Connection\Services\CredentialsService as BaseCredentialsService;
use SeQura\Demo\Platform\CredentialsService;
use SeQura\Demo\Repository\DemoConnectionDataRepository;
use SeQura\Demo\Repository\DemoCountryConfigRepository;
use SeQura\Demo\Repository\DemoCredentialsRepository;
use SeQura\Demo\Repository\DemoDeploymentsRepository;
use SeQura\Demo\Repository\DemoSeQuraOrderRepository;
use SeQura\Demo\Webhook\DemoWebhookHandler;
use SeQura\Demo\Webhook\DemoWebhookValidator;
use SeQura\Demo\Services\DemoDeploymentsService;

/**
 * Full bootstrap for the SeQura Demo application.
 *
 * Registers all platform services, initialises the integration-core
 * BootstrapComponent, then overrides repository registrations with
 * demo-specific implementations.
 */
final class Bootstrap
{
    /**
     * Initialise the integration-core with demo-specific services.
     *
     * @return void
     */
    public static function init(): void
    {
        // ---------------------------------------------------------------
        // Section 1: Register platform services BEFORE core init
        // These must be available because BootstrapComponent::init() may
        // reference them when wiring up core services, controllers, etc.
        // ---------------------------------------------------------------

        ServiceRegister::registerService(
            Configuration::CLASS_NAME,
            static function () {
                return DemoConfiguration::getInstance();
            }
        );

        ServiceRegister::registerService(
            ShopLoggerAdapter::CLASS_NAME,
            static function () {
                return new DemoLoggerAdapter();
            }
        );

        ServiceRegister::registerService(
            EncryptorInterface::class,
            static function () {
                return new DemoEncryptor();
            }
        );

        ServiceRegister::registerService(
            OrderCreationInterface::class,
            static function () {
                return new DemoOrderCreation();
            }
        );

        ServiceRegister::registerService(
            MerchantDataProviderInterface::class,
            static fn() => new DemoMerchantDataProvider(
                ServiceRegister::getService(SeQuraOrderRepositoryInterface::class)
            )
        );

        ServiceRegister::registerService(
            StoreIntegrationServiceInterface::class,
            static function () {
                return new DemoStoreIntegration();
            }
        );

        ServiceRegister::registerService(
            ShopOrderService::class,
            static fn() => new DemoShopOrderService(
                ServiceRegister::getService(SeQuraOrderRepositoryInterface::class)
            )
        );

        ServiceRegister::registerService(
            ShopOrderStatusesServiceInterface::class,
            static fn() => new DemoShopOrderStatuses()
        );

        // ---------------------------------------------------------------
        // Section 2: Initialise the core BootstrapComponent
        // Registers all core services, controllers, proxies, events, and
        // repository wrappers with lazy callables.
        // ---------------------------------------------------------------

        BootstrapComponent::init();

        // ---------------------------------------------------------------
        // Section 3: Override registrations AFTER core init
        // The core registers repos with RepositoryRegistry and services
        // with repo dependencies; we replace with demo implementations.
        // ---------------------------------------------------------------

        // --- File-backed repositories ---
        ServiceRegister::registerService(
            BaseCredentialsService::class,
            static fn() => new CredentialsService(
                ServiceRegister::getService(ConnectionProxyInterface::class),
                ServiceRegister::getService(CredentialsRepositoryInterface::class),
                ServiceRegister::getService(CountryConfigurationRepositoryInterface::class),
                ServiceRegister::getService(PaymentMethodRepositoryInterface::class)
            )
        );

        ServiceRegister::registerService(
            ConnectionDataRepositoryInterface::class,
            static fn() => new DemoConnectionDataRepository()
        );

        ServiceRegister::registerService(
            DeploymentsRepositoryInterface::class,
            static fn() => new DemoDeploymentsRepository()
        );

        ServiceRegister::registerService(
            CredentialsRepositoryInterface::class,
            static fn() => new DemoCredentialsRepository()
        );

        ServiceRegister::registerService(
            CountryConfigurationRepositoryInterface::class,
            static fn() => new DemoCountryConfigRepository()
        );

        ServiceRegister::registerService(
            SeQuraOrderRepositoryInterface::class,
            static fn() => new DemoSeQuraOrderRepository()
        );

        ServiceRegister::registerService(
            DemoSeQuraOrderRepository::class,
            static fn() => ServiceRegister::getService(SeQuraOrderRepositoryInterface::class)
        );

        // --- No-op repos (satisfy constructor injection for unused services) ---

        ServiceRegister::registerService(
            PaymentMethodRepositoryInterface::class,
            static fn() => new class implements PaymentMethodRepositoryInterface {
                public function getPaymentMethods(string $merchantId): array
                {
                    return [];
                }

                public function setPaymentMethod(string $merchantId, SeQuraPaymentMethod $paymentMethod): void
                {
                }

                public function deletePaymentMethods(string $merchantId): void
                {
                }

                public function deleteAllPaymentMethods(): void
                {
                }
            }
        );

        ServiceRegister::registerService(
            OrderStatusSettingsRepositoryInterface::class,
            static fn() => new class implements OrderStatusSettingsRepositoryInterface {
                public function getOrderStatusMapping(): ?array
                {
                    return null;
                }
                public function setOrderStatusMapping(array $orderStatusMapping): void
                {
                }
                public function deleteOrderStatusMapping(): void
                {
                }
            }
        );

        ServiceRegister::registerService(
            StoreIntegrationRepositoryInterface::class,
            static fn() => new class implements StoreIntegrationRepositoryInterface {
                public function setStoreIntegration(StoreIntegration $storeIntegration): void
                {
                }

                public function deleteStoreIntegration(): void
                {
                }

                public function getStoreIntegration(): ?StoreIntegration
                {
                    return null;
                }
            }
        );

        // --- Service overrides ---

        ServiceRegister::registerService(
            DeploymentsService::class,
            static fn() => new DemoDeploymentsService(
                ServiceRegister::getService(DeploymentsProxyInterface::class),
                ServiceRegister::getService(DeploymentsRepositoryInterface::class),
                ServiceRegister::getService(ConnectionDataRepositoryInterface::class)
            )
        );

        // --- Webhook overrides ---

        ServiceRegister::registerService(
            WebhookValidator::class,
            static fn() => new DemoWebhookValidator(
                ServiceRegister::getService(SeQuraOrderRepositoryInterface::class)
            )
        );

        ServiceRegister::registerService(
            WebhookHandler::class,
            static fn() => new DemoWebhookHandler(
                ServiceRegister::getService(ShopOrderService::class),
                ServiceRegister::getService(MerchantOrderRequestBuilder::class),
                ServiceRegister::getService(SeQuraOrderRepositoryInterface::class)
            )
        );
    }
}
