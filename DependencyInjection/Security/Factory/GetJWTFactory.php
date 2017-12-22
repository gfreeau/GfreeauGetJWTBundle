<?php

namespace Gfreeau\Bundle\GetJWTBundle\DependencyInjection\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;

class GetJWTFactory implements SecurityFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint)
    {
        $providerId = 'security.authentication.provider.get.jwt.'.$id;
        $container
            ->setDefinition($providerId, $this->createChildDefinition($config['authentication_provider']))
            ->replaceArgument(0, new Reference($userProvider))
            ->replaceArgument(1, new Reference($config['user_checker']))
            ->replaceArgument(2, $id)
        ;

        $listenerId = 'security.authentication.listener.get.jwt.'.$id;
        $listener = $container
            ->setDefinition($listenerId, $this->createChildDefinition('gfreeau_get_jwt.security.authentication.listener'))
            ->replaceArgument(2, $id)
            ->replaceArgument(5, $config)
        ;

        if (isset($config['success_handler'])) {
            $listener->replaceArgument(3, new Reference($config['success_handler']));
        }

        if ($config['throw_exceptions']) {
            // remove failure handler
            $listener->replaceArgument(4, null);
        } else if (isset($config['failure_handler'])) {
            $listener->replaceArgument(4, new Reference($config['failure_handler']));
        }

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
                ->scalarNode('success_handler')
                    ->defaultValue('lexik_jwt_authentication.handler.authentication_success')
                ->end()
                ->scalarNode('failure_handler')
                    ->defaultValue('lexik_jwt_authentication.handler.authentication_failure')
                ->end()
                ->booleanNode('throw_exceptions')
                    ->defaultFalse()
                ->end()
                ->scalarNode('authentication_provider')
                    ->defaultValue('security.authentication.provider.dao')
                ->end()
                ->scalarNode('user_checker')
                    ->defaultValue('security.user_checker')
                    ->treatNullLike('security.user_checker')
                    ->info('The UserChecker to use when authenticating users in this firewall.')
                ->end()
            ->end();
    }

    private function createChildDefinition($parent)
    {
        if (class_exists('Symfony\Component\DependencyInjection\ChildDefinition')) {
            return new ChildDefinition($parent);
        }

        return new DefinitionDecorator($parent);
    }
}
