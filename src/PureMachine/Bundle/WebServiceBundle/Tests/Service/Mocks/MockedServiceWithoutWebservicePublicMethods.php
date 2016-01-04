<?php

namespace PureMachine\Bundle\WebServiceBundle\Tests\Service\Mocks;

use PureMachine\Bundle\WebServiceBundle\WebService\BaseWebService;
use PureMachine\Bundle\WebServiceBundle\Service\Annotation as PM;

/**
 * @PM\WSNamespace("PureMachine/WS/Test")
 */
class MockedServiceWithoutWebservicePublicMethods extends BaseWebService
{

    public function foo()
    {

    }

    public function bar()
    {

    }

}
