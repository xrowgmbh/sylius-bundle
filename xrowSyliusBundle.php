<?php

namespace xrow\syliusBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use xrow\syliusBundle\Security\SyliusUserFactory;

class xrowSyliusBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new SyliusUserFactory());
    }
}