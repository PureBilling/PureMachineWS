<?php
namespace PureMachine\Bundle\WebServiceBundle\WebService;

use PureMachine\Bundle\WebServiceBundle\Service\Annotation as PM;

/**
 * @PM\WSNamespace("PureMachine/Doc")
 */
class WSDoc extends SymfonyBaseWebService
{
    /**
     * @PM\WebService("WSReference")
     * @PM\InputClass("PureMachine\Bundle\SDKBundle\Store\Type\String")
     * @PM\ReturnClass("PureMachine\Bundle\SDKBundle\Store\WSDoc\WSReference")
     */
    public function wsReferenceV1($wsName)
    {
        $name = $wsName->getValue();

        return $this->get('pure_machine.sdk.documentation_manager')
                    ->getWSReference($name);
    }
}
