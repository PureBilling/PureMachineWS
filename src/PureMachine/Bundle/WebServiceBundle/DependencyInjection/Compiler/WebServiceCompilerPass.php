<?php

namespace PureMachine\Bundle\WebServiceBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class WebServiceCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('pureMachine.sdk.webServiceManager')) {
            return;
        }

        $definition = $container->getDefinition('pureMachine.sdk.webServiceManager');

        $taggedServices = $container->findTaggedServiceIds('puremachine.webservice');

        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall('addService', array($id, new Reference($id)));
        }
    }
}
