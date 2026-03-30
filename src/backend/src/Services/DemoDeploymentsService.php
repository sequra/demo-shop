<?php

declare(strict_types=1);

namespace SeQura\Demo\Services;

use SeQura\Core\BusinessLogic\Domain\Connection\RepositoryContracts\ConnectionDataRepositoryInterface;
use SeQura\Core\BusinessLogic\Domain\Deployments\Exceptions\DeploymentNotFoundException;
use SeQura\Core\BusinessLogic\Domain\Deployments\Models\Deployment;
use SeQura\Core\BusinessLogic\Domain\Deployments\ProxyContracts\DeploymentsProxyInterface;
use SeQura\Core\BusinessLogic\Domain\Deployments\RepositoryContracts\DeploymentsRepositoryInterface;
use SeQura\Core\BusinessLogic\Domain\Deployments\Services\DeploymentsService;

/**
 * Overrides DeploymentsService to read from the file-backed repository
 * instead of calling the SeQura API at runtime.
 */
class DemoDeploymentsService extends DeploymentsService
{
    /**
     * @param DeploymentsProxyInterface $deploymentProxy The deployment proxy.
     * @param DeploymentsRepositoryInterface $deploymentRepository The deployments' repository.
     * @param ConnectionDataRepositoryInterface $connectionDataRepository The connection data repository.
     */
    public function __construct(
        DeploymentsProxyInterface $deploymentProxy,
        private readonly DeploymentsRepositoryInterface $deploymentRepository,
        ConnectionDataRepositoryInterface $connectionDataRepository,
    ) {
        parent::__construct($deploymentProxy, $deploymentRepository, $connectionDataRepository);
    }

    /** @inheritDoc */
    public function getDeployments(): array
    {
        return $this->deploymentRepository->getDeployments();
    }

    /**
     * @inheritDoc
     *
     * @throws DeploymentNotFoundException
     */
    public function getDeploymentById(string $deploymentId): Deployment
    {
        $deployment = $this->deploymentRepository->getDeploymentById($deploymentId);

        if ($deployment === null) {
            throw new DeploymentNotFoundException();
        }

        return $deployment;
    }
}
