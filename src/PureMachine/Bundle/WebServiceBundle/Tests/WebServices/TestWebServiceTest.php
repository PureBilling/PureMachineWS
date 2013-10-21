<?php
namespace PureMachine\Bundle\WebServiceBundle\Tests\WebServices;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use PureMachine\Bundle\WebServiceBundle\Exception\WebServiceException;
use PureMachine\Bundle\SDKBundle\Store\ExceptionStore;
use PureMachine\Bundle\WebServiceBundle\Store\TestStoreA;
use PureMachine\Bundle\WebServiceBundle\Store\TestStoreB;
use PureMachine\Bundle\SDKBundle\Store\WebService\Response;
use PureMachine\Bundle\SDKBundle\Store\WebService\DebugErrorResponse;
use PureMachine\Bundle\SDKBundle\Store\Type\String;
use PureMachine\Bundle\SDKBundle\Store\Type\Boolean;

/**
 * @code
 * phpunit -v -c app vendor/puremachine/ws/src/PureMachine/Bundle/WebServiceBundle/Tests/WebServices/TestWebServiceTest.php
 * @endcode
 */
class TestWebServiceTest extends WebTestCase
{
    /**
     * @code
     * phpunit -v -c app --filter testCallMethodWithLocalWebServices vendor/puremachine/ws/src/PureMachine/Bundle/WebServiceBundle/Tests/WebServices/TestWebServiceTest.php
     * @endcode
     */
    public function testCallMethodWithLocalWebServices($callType=WebServiceManagerMock::ALL_LOCAL)
    {
        $client = static::createClient();

        $wsM = new WebServiceManagerMock($client->getContainer(), $callType);

        $local = true;
        if ($callType==WebServiceManagerMock::ALL_REMOTE)
            $local = false;

        /**
         * Basic local webServices tests
         */

        //Test wrong WebServiceName with no configuration
        $response = $wsM->call('Wrong/WebServiceName');
        $this->assertEquals('error', $response->getStatus());
        $this->assertTrue($response instanceof DebugErrorResponse);
        $this->assertEquals(true, $response->getLocal());
        $this->assertTrue($response->getAnswer() instanceof ExceptionStore);
        if ($callType == WebServiceManagerMock::ALL_REMOTE)
            $this->assertEquals('WS_005', $response->getAnswer()->getCode());
        else
            $this->assertEquals('WS_002', $response->getAnswer()->getCode());

        $this->assertTrue($response->getAnswer() instanceof ExceptionStore);

        //Test wrong WebServiceName with valid namespace but wrong webService
        $response = $wsM->call('PureMachine/Test/DOESNOTEXISTS');
        $this->assertEquals('error', $response->getStatus());
        $this->assertEquals(true, $response->getLocal());
        $this->assertTrue($response->getAnswer() instanceof ExceptionStore);

        if ($callType == WebServiceManagerMock::ALL_REMOTE)
            $this->assertEquals('HTTP_001', $response->getAnswer()->getCode());
        else
            $this->assertEquals('WS_002', $response->getAnswer()->getCode());

        //Existing service but with a wrong version
        $response = $wsM->call('PureMachine/Test/NoParamReturnStore', null, 'V999999');
        $this->assertEquals('error', $response->getStatus());
        $this->assertTrue($response->getAnswer() instanceof ExceptionStore);
        if ($callType == WebServiceManagerMock::ALL_REMOTE)

            $this->assertEquals('HTTP_001', $response->getAnswer()->getCode());
        else
            $this->assertEquals('WS_002', $response->getAnswer()->getCode());

        //Call WebService with default version returing a store
        $response = $wsM->call('PureMachine/Test/NoParamReturnStore');
        WebServiceException::raiseIfError($response, true);

        $this->assertTrue($response->getAnswer() instanceof TestStoreA);

        //Call WebService returing an invalid input parameter type.
        $response = $wsM->call('PureMachine/Test/StringReturnStore', 5);
        $this->assertEquals('error', $response->getStatus());
        $this->assertEquals('WS_003', $response->getAnswer()->getCode());

        //Call WebService returing an invalid store as return value
        $response = $wsM->call('PureMachine/Test/StringReturnStore', null);
        $this->assertEquals('error', $response->getStatus());
        $this->assertEquals('WS_003', $response->getAnswer()->getCode());

        //Call WebService returing the entry value
        $response = $wsM->call('PureMachine/Test/StringReturnStore',
                               new String('My super Value'));
        WebServiceException::raiseIfError($response);
        $this->assertEquals('My super Value', $response->getAnswer()->getTestString());

        //Test defaultNameSpace
        $response = $wsM->call('PureMachine/Test/CustomNameSpace/CustomNameSpaceWS');
        WebServiceException::raiseIfError($response);

        //Test Store as entry parameter returning a string
        //But we don't send a Store but a stdClass
        $data = new \stdClass();
        $data->testString = 'Store as input';
        $response = $wsM->call('PureMachine/Test/StoreReturnString', $data);
        WebServiceException::raiseIfError($response);
        $this->assertEquals('Store as input', $response->getAnswer()->getValue());

        //same with a store
        $data = new TestStoreA();
        $data->setTestString('Store as input');
        $response = $wsM->call('PureMachine/Test/StoreReturnNoParam', $data);
        WebServiceException::raiseIfError($response);

        //same but with a wrong stdClass
        $data = new \stdClass();
        $data->testStringNotDefinedInStore = 'Store as input';
        $response = $wsM->call('PureMachine/Test/StoreReturnString', $data);
        $this->assertEquals('error', $response->getStatus());
        $this->assertEquals('WS_003', $response->getAnswer()->getCode());

        //Test Store array as entry parameter returning a string
        //But we don't send a Store but a stdClass
        $data = array();
        $dataA = new \stdClass();
        $dataA->testString = 'A';
        $data[] = $dataA;
        $dataB = new \stdClass();
        $dataB->testString = 'B';
        $data[] = $dataB;
        $response = $wsM->call('PureMachine/Test/StoreArrayReturnString', $data);
        WebServiceException::raiseIfError($response);
        $this->assertEquals('B', $response->getAnswer()->getValue());

        //Test Store array as entry parameter returning a string
        //But we don't send a Store but a stdClass
        $data = array();
        $dataA = new TestStoreA();
        $dataA->setTestString('A2');
        $data[] = $dataA;
        $dataB = new TestStoreA();
        $dataB->setTestString('B2');
        $data[] = $dataB;
        $response = $wsM->call('PureMachine/Test/StoreArrayReturnString', $data);
        WebServiceException::raiseIfError($response);
        $this->assertEquals('B2', $response->getAnswer()->getValue());

        //same with a store of another type
        //Should raise an Exception
        $data = new TestStoreB();
        $data->setTestString('Store as input');
        $response = $wsM->call('PureMachine/Test/StoreReturnString', $data);
        $this->assertEquals('error', $response->getStatus());
        $this->assertEquals('WS_003', $response->getAnswer()->getCode());

        //same with a store of another type, but inside an array
        //Should raise an Exception
        $data = array();
        $dataA = new TestStoreA();
        $dataA->setTestString('A2');
        $data[] = $dataA;
        $dataB = new TestStoreB();
        $dataB->setTestString('B2');
        $data[] = $dataB;
        $response = $wsM->call('PureMachine/Test/StoreArrayReturnString', $data);
        $this->assertEquals('error', $response->getStatus());
        $this->assertEquals('WS_003', $response->getAnswer()->getCode());

        //WebService that allow two type of store as entry parameters
        $data = new TestStoreA();
        $data->setTestString('Store as input (A)');
        $response = $wsM->call('PureMachine/Test/MultipleStoreReturnString', $data);
        WebServiceException::raiseIfError($response);
        $this->assertEquals('Store as input (A)', $response->getAnswer()->getValue());

        //WebService that allow two type of store as entry parameters
        $data = new TestStoreB();
        $data->setTestString('Store as input (B)');
        $response = $wsM->call('PureMachine/Test/MultipleStoreReturnString', $data);
        WebServiceException::raiseIfError($response);
        $this->assertEquals('Store as input (B)', $response->getAnswer()->getValue());

        //idem with stdClass
        //WebService that allow two type of store as entry parameters
        $data = new \stdClass();
        $data->testString ='CHECK IF STOREB';
        $data->className = 'PureMachine\Bundle\WebServiceBundle\Store\TestStoreB';
        $response = $wsM->call('PureMachine/Test/MultipleStoreReturnString', $data);
        WebServiceException::raiseIfError($response);
        $this->assertEquals('CHECK IF STOREB', $response->getAnswer()->getValue());

        //a array with 2 stores of différent types
        $data = array();
        $dataA = new TestStoreB();
        $dataA->setTestString('B3');
        $data[] = $dataA;
        $dataB = new TestStoreA();
        $dataB->setTestString('A3');
        $data[] = $dataB;
        $response = $wsM->call('PureMachine/Test/MultipleStoreMultiTypeReturnString', $data);
        WebServiceException::raiseIfError($response);
        $this->assertEquals('A3', $response->getAnswer()->getValue());

        //same with stdClass
        //a array with 2 stores of différent types
        $data = array();
        $dataA = new \stdClass();
        $dataA->testString = 'B3';
        $dataA->className = 'PureMachine\Bundle\WebServiceBundle\Store\TestStoreB';
        $data[] = $dataA;
        $dataB = new \stdClass();
        $dataB->testString = 'A3';
        $dataB->className = 'PureMachine\Bundle\WebServiceBundle\Store\TestStoreA';
        $data[] = $dataB;
        $response = $wsM->call('PureMachine/Test/MultipleStoreMultiTypeReturnString', $data);
        WebServiceException::raiseIfError($response);
        $this->assertEquals('A3', $response->getAnswer()->getValue());

        /**
         * NEEED TO CHECK STORE RETURN VALUE AS ARRAY
         *
         * with same storeType, and with two store types.
         */

        //Should return a Store A
        $response = $wsM->call('PureMachine/Test/NoParamReturnStoreA', new Boolean(true));
        WebServiceException::raiseIfError($response);
        $this->assertTrue($response->getAnswer() instanceof TestStoreA);

        //Same, but WS return a wrong type
        $response = $wsM->call('PureMachine/Test/NoParamReturnStoreA', new Boolean(false));
        $this->assertEquals('error', $response->getStatus());
        $this->assertEquals('WS_004', $response->getAnswer()->getCode());

        //return values with two stores
        $response = $wsM->call('PureMachine/Test/NoParamReturnTwoStores', new Boolean(true));
        WebServiceException::raiseIfError($response);
        $answer = $response->getAnswer();
        $this->assertTrue($answer[0] instanceof TestStoreA);
        $this->assertTrue($answer[1] instanceof TestStoreB);

    }

    private function getResponseFromUrl($url)
    {
        $client = static::createClient();
        $client->request('GET', $url);
        $this->assertTrue($client->getResponse()->isSuccessful());
        $rawResponse = $client->getResponse()->getContent();
        $this->assertNotNull($rawResponse);
        $response = json_decode($rawResponse);
        $this->assertNotNull($response);

        return $response;
    }

    /**
     * @code
     * phpunit -v -c app --filter testCallFromUrl vendor/puremachine/ws/src/PureMachine/Bundle/WebServiceBundle/Tests/WebServices/TestWebServiceTest.php
     * @endcode
     */
    public function testCallFromUrl()
    {
        //test with a simple get parameter
        $response = $this->getResponseFromUrl('/ws/V1/PureMachine/Test/StringReturnStore');
        $this->assertEquals('error', $response->status);
        $this->assertEquals('WS_003', $response->answer->code);

        $response = $this->getResponseFromUrl('/ws/V1/PureMachine/Test/StoreReturnString');
        $this->assertEquals('error', $response->status);
        $this->assertEquals('WS_003', $response->answer->code);

        $response = $this->getResponseFromUrl('/ws/V1/PureMachine/Test/StringReturnStore?value=test%20Param');
        $this->assertEquals('test Param', $response->answer->testString);

        $response = $this->getResponseFromUrl('/ws/V1/PureMachine/Test/StringReturnStore?json={"value":"test%20Param"}');
        $this->assertEquals('test Param', $response->answer->testString);
    }

    /**
     * @code
     * phpunit -v -c app --filter testCallMethodWithRemoteWebServices vendor/puremachine/ws/src/PureMachine/Bundle/WebServiceBundle/Tests/WebServices/TestWebServiceTest.php
     * @endcode
     */
    public function testCallMethodWithRemoteWebServices()
    {
        $this->testCallMethodWithLocalWebServices(WebServiceManagerMock::ALL_REMOTE);
    }
    
    /**
     * @code
     * phpunit -v -c app --filter testStoreAutoMapping vendor/puremachine/ws/src/PureMachine/Bundle/WebServiceBundle/Tests/WebServices/TestWebServiceTest.php
     * @endcode
     */
    public function testStoreAutoMapping()
    {
        $client = static::createClient();
        $wsM = new WebServiceManagerMock($client->getContainer(), WebServiceManagerMock::ALL_REMOTE);
        
        //Try string autoMapping
        $response = $wsM->call('PureMachine/Test/StringReturnStore',
                               'string Value');
        WebServiceException::raiseIfError($response, true);
        $this->assertEquals('string Value', $response->getAnswer()->getTestString());

    }
}
