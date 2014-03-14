<?php

namespace PureMachine\Bundle\WebServiceBundle\Tests\Service;

use PureMachine\Bundle\WebServiceBundle\Service\WebServiceManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @code
 * phpunit -v -c app vendor/puremachine/ws/src/PureMachine/Bundle/WebServiceBundle/Tests/Service/DocumentationManagerTest.php
 * @endcode
 */
class DocumentationManagerTest extends WebTestCase
{

    /**
     * Checks if the addService method for a given service will not try to parse
     * and add method without the main webservice annotation
     *
     * @code
     * phpunit -v --filter testWSReference -c app vendor/puremachine/ws/src/PureMachine/Bundle/WebServiceBundle/Tests/Service/DocumentationManagerTest.php
     * @endcode
     */
    public function testWSReference()
    {
        $symfony = static::createClient();
        $wsManager = $symfony->getContainer()->get('pureMachine.sdk.webServiceManager');

        $answer = $wsManager->call('PureMachine/Doc/WSReference','PureBilling/Charge/Capture');

        print $answer->getAnswer();
    }

}
