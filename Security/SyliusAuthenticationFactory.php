<?php

namespace xrow\syliusBundle\Security;

use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\FormLoginFactory;

class SyliusAuthenticationFactory extends FormLoginFactory
{
    public function getKey()
    {
        return 'xrowsylius';
    }

    protected function getListenerId()
    {
        return 'security.authentication.listener.form';
    }

    protected function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId)
    {
        $provider = 'security.authentication_provider.xrowsylius.'.$id;
        $container
            ->setDefinition($provider, new DefinitionDecorator('security.authentication_provider.xrowsylius'))
            ->replaceArgument(0, new Reference($userProviderId))
            ->replaceArgument(2, $id);

        return $provider;
    }
}