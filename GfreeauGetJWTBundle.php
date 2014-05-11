<?php

namespace Gfreeau\Bundle\GetJWTBundle;

use Gfreeau\Bundle\GetJWTBundle\DependencyInjection\Security\Factory\GetJWTFactory;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class GfreeauGetJWTBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        /** @var SecurityExtension $extension */
        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new GetJWTFactory());
    }
}
