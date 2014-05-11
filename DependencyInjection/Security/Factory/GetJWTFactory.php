<?php

namespace Gfreeau\Bundle\GetJWTBundle\DependencyInjection\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;

class GetJWTFactory implements SecurityFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint)
    {
        $providerId = 'security.authentication.provider.dao.'.$id;
        $container
            ->setDefinition($providerId, new DefinitionDecorator('security.authentication.provider.dao'))
            ->replaceArgument(0, new Reference($userProvider))
            ->replaceArgument(2, $id)
        ;

        $listenerId = 'security.authentication.listener.get.jwt.'.$id;
        $listener = $container
            ->setDefinition($listenerId, new DefinitionDecorator('gfreeau_get_jwt.security.authentication.listener'))
            ->replaceArgument(2, $id)
            ->replaceArgument(3, $config)
        ;

        return array($providerId, $listenerId, $defaultEntryPoint);
    }

    /**
     * {@inheritdoc}
     */
    public function getPosition()
    {
        return 'pre_auth';
    }

    /**
     * {@inheritdoc}
     */
    public function getKey()
    {
        return 'gfreeau_get_jwt';
    }

    /**
     * {@inheritdoc}
     */
    public function addConfiguration(NodeDefinition $node)
    {
        $node
            ->children()
                ->scalarNode('username_parameter')
                    ->defaultValue('username')
                ->end()
                ->scalarNode('password_parameter')
                    ->defaultValue('password')
                ->end()
                ->integerNode('ttl')
                    ->defaultValue(86400)
                ->end()
                ->booleanNode('post_only')
                    ->defaultTrue()
                ->end()
            ->end();
    }

}