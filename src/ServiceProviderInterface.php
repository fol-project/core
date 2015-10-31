<?php

namespace Fol;

use Interop\Container\ContainerInterface;

/**
 * Interface used to register services.
 */
interface ServiceProviderInterface
{
    /**
     * Register the service.
     */
    public function register(ContainerInterface $app);
}
