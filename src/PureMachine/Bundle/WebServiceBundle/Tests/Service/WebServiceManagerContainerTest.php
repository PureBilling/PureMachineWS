<?php

namespace PureMachine\Bundle\WebServiceBundle\Tests\Service;

use PureMachine\Bundle\WebServiceBundle\Tests\Service\Mocks\Store\SampleStoreWithContainerAwareInterface;
use PureMachine\Bundle\WebServiceBundle\Tests\Service\Mocks\MockedServiceStoreWithContainerTestService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @code
 * phpunit -v -c app vendor/puremachine/ws/src/PureMachine/Bundle/WebServiceBundle/Tests/Service/WebServiceManagerContainerTest.php
 * @endcode
 *
 * @author Albert Lacarta <urodoz@gmail.com>
 */
class WebServiceManagerContainerTest extends WebTestCase
{

    /**
     * Creates a service with a store implementing the ContainerAwareInterface and checks
     * if its reachable from the store once it arrives to the service
     *
     * @code
     * phpunit -v --filter testContainerReachableFromStore -c app vendor/puremachine/ws/src/PureMachine/Bundle/WebServiceBundle/Tests/Service/WebServiceManagerContainerTest.php
     * @endcode
     */
    public function testContainerReachableFromStore()
    {
        $client = static::createClient();
        $serviceId = strtolower(uniqid());
        $container = $client->getContainer();

        //Created the mocked service and inject it on pure machine service collection
        $mockedService = new MockedServiceStoreWithContainerTestService();
        $container->set($serviceId, $mockedService);

        $wsManager = $container->get("pureMachine.sdk.webServiceManager");
        $wsManager->addService($serviceId, $mockedService);

        //Call local service
        $answer = $container->get("pure_machine.sdk.web_service_client")->call(
                "PureMachine/Test/StoreWithContainerTest",
                new SampleStoreWithContainerAwareInterface()
                );

        //'yes' returned from the service if reachable : See on MockedServiceWithoutWebservicePublicMethods
        $this->assertEquals("success", $answer->getStatus());
        $this->assertEquals("yes", $answer->getAnswer()->getValue());
    }

}
