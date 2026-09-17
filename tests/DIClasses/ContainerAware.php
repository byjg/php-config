<?php

namespace Tests\DIClasses;

use Psr\Container\ContainerInterface;

/**
 * Stands in for a service that resolves collaborators on its own — a router
 * instantiating controllers, for example.
 */
class ContainerAware
{
    public function __construct(protected ?ContainerInterface $container = null)
    {
    }

    public function withContainer(ContainerInterface $container): static
    {
        $this->container = $container;
        return $this;
    }

    public function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }

    /**
     * Resolves through the injected container, so a test can prove the container is
     * genuinely usable rather than merely non-null.
     */
    public function resolve(string $id): mixed
    {
        return $this->container->get($id);
    }
}
