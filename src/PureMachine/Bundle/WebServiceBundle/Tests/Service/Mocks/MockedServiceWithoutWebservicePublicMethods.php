<?php

namespace PureMachine\Bundle\WebServiceBundle\Tests\Service\Mocks;

use JMS\DiExtraBundle\Annotation\Service;
use JMS\DiExtraBundle\Annotation\Tag;
use PureMachine\Bundle\WebServiceBundle\WebService\BaseWebService;
use PureMachine\Bundle\WebServiceBundle\Service\Annotation as PM;

/**
 * @Service
 * @Tag("puremachine.webservice")
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
