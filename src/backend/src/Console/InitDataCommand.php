<?php

declare(strict_types=1);

namespace SeQura\Demo\Console;

use Random\RandomException;
use SeQura\Core\BusinessLogic\Domain\Connection\Exceptions\InvalidEnvironmentException;
use SeQura\Core\BusinessLogic\Domain\Connection\Models\AuthorizationCredentials;
use SeQura\Core\BusinessLogic\Domain\Connection\Models\ConnectionData;
use SeQura\Core\BusinessLogic\Domain\Connection\RepositoryContracts\ConnectionDataRepositoryInterface;
use SeQura\Core\BusinessLogic\Domain\Connection\RepositoryContracts\CredentialsRepositoryInterface;
use SeQura\Core\BusinessLogic\Domain\Connection\Services\CredentialsService;
use SeQura\Core\BusinessLogic\Domain\CountryConfiguration\Models\CountryConfiguration;
use SeQura\Core\BusinessLogic\Domain\CountryConfiguration\RepositoryContracts\CountryConfigurationRepositoryInterface;
use SeQura\Core\BusinessLogic\Domain\Deployments\ProxyContracts\DeploymentsProxyInterface;
use SeQura\Core\BusinessLogic\Domain\Deployments\RepositoryContracts\DeploymentsRepositoryInterface;
use SeQura\Core\BusinessLogic\SeQuraAPI\BaseProxy;
use SeQura\Core\Infrastructure\ServiceRegister;
use SeQura\Demo\Config;
use SeQura\Demo\Repository\DemoBaseRepository;

/**
 * CLI command that fetches deployments and credentials from the SeQura API
 * and persists them as encrypted files for use by the file-backed repositories.
 *
 * Usage: php backend/bin/init-data.php
 */
final class InitDataCommand
{
    /**
     * Fetch deployments and credentials from the SeQura API and persist them.
     *
     * @return int Exit code (0 = success, 1 = failure).
     *
     * @throws InvalidEnvironmentException|RandomException
     */
    public function execute(): int
    {
        echo "Fetching deployments from SeQura API...\n";

        try {
            /** @var DeploymentsProxyInterface $deploymentsProxy */
            $deploymentsProxy = ServiceRegister::getService(DeploymentsProxyInterface::class);
            $deployments = $deploymentsProxy->getDeployments();
        } catch (\Throwable $e) {
            echo "ERROR: Failed to fetch deployments: {$e->getMessage()}\n";

            return 1;
        }

        if (empty($deployments)) {
            echo "WARNING: No deployments returned from API.\n";

            return 1;
        }

        echo "Found " . count($deployments) . " deployment(s).\n";

        /** @var CredentialsService $credentialsService */
        $credentialsService = ServiceRegister::getService(CredentialsService::class);

        /** @var ConnectionDataRepositoryInterface&DemoBaseRepository $connectionRepo */
        $connectionRepo = ServiceRegister::getService(ConnectionDataRepositoryInterface::class);

        /** @var CountryConfigurationRepositoryInterface&DemoBaseRepository $countryConfigRepo */
        $countryConfigRepo = ServiceRegister::getService(CountryConfigurationRepositoryInterface::class);

        /** @var CredentialsRepositoryInterface&DemoBaseRepository $credentialsRepo */
        $credentialsRepo = ServiceRegister::getService(CredentialsRepositoryInterface::class);

        /** @var DeploymentsRepositoryInterface&DemoBaseRepository $deploymentsRepo */
        $deploymentsRepo = ServiceRegister::getService(DeploymentsRepositoryInterface::class);

        // Populate deployments in the repository BEFORE validating credentials,
        // because ConnectionProxyFactory::build() calls DeploymentsService::getDeploymentById()
        // to resolve the API base URL for each deployment.
        $deploymentsRepo->setDeployments($deployments);

        $accountKey = Config::get('SEQURA_ACCOUNT_KEY', '');
        $accountSecret = Config::get('SEQURA_ACCOUNT_SECRET', '');
        $allCountryConfigs = [];

        foreach ($deployments as $deployment) {
            $connectionData = new ConnectionData(
                BaseProxy::TEST_MODE,
                null,
                $deployment->getId(),
                new AuthorizationCredentials($accountKey, $accountSecret)
            );

            try {
                $credentials = $credentialsService->validateAndUpdateCredentials($connectionData);

                foreach ($credentials as $credential) {
                    $allCountryConfigs[$credential->getCountry()] = new CountryConfiguration(
                        $credential->getCountry(),
                        $credential->getMerchantId()
                    );
                }

                $connectionRepo->setConnectionData($connectionData);

                echo "  Deployment {$deployment->getId()}: " . count($credentials) . " credential(s)\n";
            } catch (\Throwable $e) {
                echo "  Deployment {$deployment->getId()} skipped: {$e->getMessage()}\n";
            }
        }

        if (!empty($allCountryConfigs)) {
            $countryConfigRepo->setCountryConfiguration(array_values($allCountryConfigs));
        }

        // Write all repositories to encrypted files
        $credentialsRepo->writeToFile();
        $connectionRepo->writeToFile();
        $countryConfigRepo->writeToFile();
        $deploymentsRepo->writeToFile();

        echo "\nEncrypted data files written successfully.\n";

        return 0;
    }
}
