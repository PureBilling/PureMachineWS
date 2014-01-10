<?php
namespace PureMachine\Bundle\WebServiceBundle\WebService;

use JMS\DiExtraBundle\Annotation\Service;
use JMS\DiExtraBundle\Annotation\Tag;

use PureMachine\Bundle\WebServiceBundle\Service\Annotation as PM;
use PureMachine\Bundle\WebServiceBundle\Store\TestStoreA;
use PureMachine\Bundle\WebServiceBundle\Store\TestStoreB;
use PureMachine\Bundle\SDKBundle\Store\Base\BaseStore;
use PureMachine\Bundle\SDKBundle\Store\Type\String;
use PureMachine\Bundle\SDKBundle\Store\Type\Boolean;
use PureMachine\Bundle\WebServiceBundle\Exception\WebServiceException;

/**
 * @Service
 * @Tag("puremachine.webservice")
 * @PM\WSNamespace("PureMachine/Test")
 */
class TestWS extends BaseWebService
{
    /**
     * @PM\WebService("NoParamReturnString")
     * @PM\ReturnClass("PureMachine\Bundle\SDKBundle\Store\Type\String")
     */
    public function simpleTest($test)
    {
        return new String('testAnswer');
    }

    /**
     * @PM\WebService("CustomNameSpaceWS")
     * @PM\WSNamespace("PureMachine/Test/CustomNameSpace")
     * @PM\ReturnClass("PureMachine\Bundle\SDKBundle\Store\Type\String")
     */
    public function customNameSpaceTest()
    {
        return new String('testAnswer custome namespace');
    }

    /**
     * @PM\WebService("NoParamReturnStore")
     * @PM\ReturnClass("PureMachine\Bundle\WebServiceBundle\Store\TestStoreA")
     */
    public function simpleTest2()
    {
        $store = new TestStoreA();
        $store->setTestString('VALUE IN STORE');

        return $store;
    }

    /**
     * @PM\WebService("StringReturnStore")
     * @PM\InputClass("PureMachine\Bundle\SDKBundle\Store\Type\String")
     * @PM\ReturnClass("PureMachine\Bundle\WebServiceBundle\Store\TestStoreA")
     */
    public function simpleTest4(String $value)
    {
        $store = new TestStoreA();
        $store->setTestString($value->getValue());

        return $store;
    }

    /**
     * @PM\WebService("StoreReturnNoParam")
     * @PM\InputClass("PureMachine\Bundle\WebServiceBundle\Store\TestStoreA")
     * @PM\ReturnClass("PureMachine\Bundle\SDKBundle\Store\Type\String")
     */
    public function simpleTest41(TestStoreA $store)
    {
        if (!$store instanceof TestStoreA)
            throw new WebServiceException("should be a TestStoreA (in WS)", WebServiceException::WS_003);

        return new String('here');
    }

    /**
     * @PM\WebService("StoreReturnString")
     * @PM\InputClass("PureMachine\Bundle\WebServiceBundle\Store\TestStoreA")
     * @PM\ReturnClass("PureMachine\Bundle\SDKBundle\Store\Type\String")
     */
    public function simpleTest5(TestStoreA $store)
    {
        if (!$store instanceof TestStoreA)
            throw new WebServiceException("should be a TestStoreA (in WS)", WebServiceException::WS_003);

        return new String($store->getTestString());
    }

    /**
     * @PM\WebService("StoreArrayReturnString")
     * @PM\InputClass("PureMachine\Bundle\WebServiceBundle\Store\TestStoreA", isArray=true)
     * @PM\ReturnClass("PureMachine\Bundle\SDKBundle\Store\Type\String")
     */
    public function simpleTest6(array $stores)
    {
        foreach($stores as $store)
            if (!$store instanceof BaseStore)
                throw new WebServiceException("should be a TestStoreA (in WS), it's a " . get_class($store), WebServiceException::WS_003);

        return new String($stores[1]->getTestString());
    }

    /**
     * @PM\WebService("MultipleStoreReturnString")
     * @PM\InputClass({"PureMachine\Bundle\WebServiceBundle\Store\TestStoreA", "PureMachine\Bundle\WebServiceBundle\Store\TestStoreB"})
     * @PM\ReturnClass("PureMachine\Bundle\SDKBundle\Store\Type\String")
     */
    public function simpleTest7(BaseStore $store)
    {
        if (!($store instanceof TestStoreA) && !($store instanceof TestStoreB))
            throw new WebServiceException("should be a TestStoreA OR B (in WS), but it's a " . get_class($store), WebServiceException::WS_003);
        $value = $store->getTestString();

        if ($value == 'CHECK IF STOREB') {
             if (!($store instanceof TestStoreB))
            throw new WebServiceException("should be a TestStoreB (in WSsimpleTest7 2), but it's a " . get_class($store), WebServiceException::WS_003);
        }

        return new String($value);
    }

    /**
     * @PM\WebService("MultipleStoreMultiTypeReturnString")
     * @PM\InputClass(classes={"PureMachine\Bundle\WebServiceBundle\Store\TestStoreA", "PureMachine\Bundle\WebServiceBundle\Store\TestStoreB"}, isArray="true")
     * @PM\ReturnClass("PureMachine\Bundle\SDKBundle\Store\Type\String")
     */
    public function simpleTest8(array $store)
    {
        if (!($store[0] instanceof TestStoreB)) {
            throw new WebServiceException("should be a TestStoreB, but it's a " . get_class($store), WebServiceException::WS_003);
        }

        if (!($store[1] instanceof TestStoreA)) {
            throw new WebServiceException("should be a TestStoreB, but it's a " . get_class($store), WebServiceException::WS_003);
        }

        return new String($store[1]->getTestString());
    }

    /**
     * @PM\WebService("NoParamReturnStoreA")
     * @PM\InputClass("PureMachine\Bundle\SDKBundle\Store\Type\Boolean")
     * @PM\ReturnClass("PureMachine\Bundle\WebServiceBundle\Store\TestStoreA")
     */
    public function simpleTest9($returnStoreA)
    {
        if (!$returnStoreA->getValue()) return 'testAnswer';

        $store = new TestStoreA();
        $store->setTestString('simpleTest9');

        return $store;
    }

    /**
     * @PM\WebService("NoParamReturnTwoStores")
     * @PM\ReturnClass(classes={"PureMachine\Bundle\WebServiceBundle\Store\TestStoreB", "PureMachine\Bundle\WebServiceBundle\Store\TestStoreA"}, isArray=true)
     */
    public function simpleTest10()
    {
        $a = array();
        $store = new TestStoreA();
        $store->setTestString('simpleTest10 A');
        $a[] = $store;
        $store = new TestStoreB();
        $store->setTestString('simpleTest10 B');
        $a[] = $store;

        return $a;
    }
}
