<?php

namespace PureMachine\Bundle\WebServiceBundle\Tests\Service\Mocks;

use JMS\DiExtraBundle\Annotation\Service;
use JMS\DiExtraBundle\Annotation\Tag;
use PureMachine\Bundle\WebServiceBundle\WebService\BaseWebService;
use PureMachine\Bundle\WebServiceBundle\Service\Annotation as PM;
use PureMachine\Bundle\WebServiceBundle\Tests\Service\Mocks\Store\SampleStoreWithContainerAwareInterface;
use PureMachine\Bundle\SDKBundle\Store\Type\String;

/**
 * @Service
 * @Tag("puremachine.webservice")
 * @PM\WSNamespace("PureMachine/Test")
 */
class MockedServiceStoreWithContainerTestService extends BaseWebService
{

    /**
     * @PM\WebService("StoreWithContainerTest")
     * @PM\InputClass("PureMachine\Bundle\WebServiceBundle\Tests\Service\Mocks\Store\SampleStoreWithContainerAwareInterface")
     * @PM\ReturnClass("PureMachine\Bundle\SDKBundle\Store\Type\String")
     */
    public function persistProfile(SampleStoreWithContainerAwareInterface $store)
    {
        $response =  new String();
        ($store->isContainerReachable()) ? $response->setValue("yes") : $response->setValue("no");

        return $response;
    }

}
