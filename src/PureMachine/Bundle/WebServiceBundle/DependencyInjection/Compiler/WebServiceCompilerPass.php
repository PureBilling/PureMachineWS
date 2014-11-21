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

        $data = array();
        foreach ($taggedServices as $id => $attributes) {
            $data[] = array("id" => $id, "object" => new Reference($id));
        }

        $definition->addMethodCall('addServices', array($data));
    }
}
