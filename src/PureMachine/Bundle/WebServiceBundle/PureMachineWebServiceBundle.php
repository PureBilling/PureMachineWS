<?php

namespace PureMachine\Bundle\WebServiceBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use PureMachine\Bundle\WebServiceBundle\DependencyInjection\Compiler\WebServiceCompilerPass;

class PureMachineWebServiceBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new WebServiceCompilerPass());
    }
}
