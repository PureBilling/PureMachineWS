<?php

namespace PureMachine\Bundle\WebServiceBundle\Tests\Service;

use PureMachine\Bundle\WebServiceBundle\Tests\Service\Mocks\MockedServiceWithoutWebservicePublicMethods;
use PureMachine\Bundle\WebServiceBundle\Service\WebServiceManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @code
 * phpunit -v -c app vendor/puremachine/ws/src/PureMachine/Bundle/WebServiceBundle/Tests/Service/WebServiceManagerTest.php
 * @endcode
 */
class WebServiceManagerTest extends WebTestCase
{

    /**
     * Checks if the addService method for a given service will not try to parse
     * and add method without the main webservice annotation
     *
     * @code
     * phpunit -v --filter testNoServiceDefinedNoServiceAdded -c app vendor/puremachine/ws/src/PureMachine/Bundle/WebServiceBundle/Tests/Service/WebServiceManagerTest.php
     * @endcode
     */
    public function testNoServiceDefinedNoServiceAdded()
    {
        $client = static::createClient();
        $serviceId = strtolower(uniqid());
        $container = $client->getContainer();

        //Create a new service and inject on the container
        $mockedService = new MockedServiceWithoutWebservicePublicMethods();
        $container->set($serviceId, $mockedService);

        //Call webService Manager
        $wsManager = $container->get("pureMachine.sdk.webServiceManager");
        $this->assertTrue($wsManager instanceof WebServiceManager);

        //Original schemas (before apply the addService with the mocked class)
        $originalSchemas = $wsManager->getSchemas();
        $originalServices = array_keys($originalSchemas);

        $wsManager->addService($serviceId, $mockedService);

        //The services should be the same as before apply the addService method
        $afterSchemas = $wsManager->getSchemas();
        $afterServices = array_keys($afterSchemas);

        $this->assertEquals(0, count(array_diff($originalServices, $afterServices)));
    }

}
