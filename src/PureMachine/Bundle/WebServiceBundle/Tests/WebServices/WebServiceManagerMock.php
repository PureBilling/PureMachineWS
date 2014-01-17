<?php
namespace PureMachine\Bundle\WebServiceBundle\Tests\WebServices;

use PureMachine\Bundle\SDKBundle\Service\WebServiceClient;
use PureMachine\Bundle\WebServiceBundle\Service\Annotation as PM;

class WebServiceManagerMock extends WebServiceClient
{
    const ALL_LOCAL = 'local';
    const ALL_REMOTE = 'remote';
    const DEFAULT_CALL = 'default';

    private $callType = null;

    public function __construct($container, $callType=self::DEFAULT_CALL)
    {
        $this->callType = $callType;
        parent::setContainer($container);
        $manager = $container->get('pureMachine.sdk.webServiceManager');
        $this->webServices = $manager->getSchemas();
    }

    public function call($webServiceName, $inputData=null,
                              $version=PM\WebService::DEFAULT_VERSION)
    {
        $manager = $this->getContainer()->get('pureMachine.sdk.webServiceManager');
        $client = $this->getContainer()->get('pure_machine.sdk.web_service_client');

        switch ($this->callType) {
            case self::ALL_LOCAL:
                return $manager->LocalCall($webServiceName, $inputData, $version);
            case self::ALL_REMOTE:
                return $client->remoteCall($webServiceName, $inputData, $version);
        }

        return $client->call($webServiceName, $inputData, $version);
    }
}
