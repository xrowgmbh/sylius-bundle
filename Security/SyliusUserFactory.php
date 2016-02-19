<?php

namespace xrow\syliusBundle\Security;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;

class SyliusUserFactory implements SecurityFactoryInterface
{
    public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint)
    {
        $providerId = 'security.authentication.provider.xrowsylius.'.$id;
            $container
                ->setDefinition($providerId, new DefinitionDecorator('xrowsylius.security.authentication.provider'))
                ->replaceArgument(0, new Reference($userProvider))
        ;

        $listenerId = 'security.authentication.listener.xrowsylius.'.$id;
        $listener = $container->setDefinition($listenerId, new DefinitionDecorator('xrowsylius.security.authentication.listener'));

        return array($providerId, $listenerId, $defaultEntryPoint);
    }

    public function getPosition()
    {
        return 'http';
    }

    public function getKey()
    {
        return 'xrowsylius';
    }

    public function addConfiguration(NodeDefinition $node)
    {
    }
}