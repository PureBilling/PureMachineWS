<?php
namespace PureMachine\Bundle\WebServiceBundle\Service;

use Doctrine\Common\Cache\PhpFileCache;

use PureMachine\Bundle\SDKBundle\Store\Base\BaseStore;
use PureMachine\Bundle\StoreBundle\Manager\StoreManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use PureMachine\Bundle\SDKBundle\Store\WebService\Response as StoreResponse;
use PureMachine\Bundle\WebServiceBundle\Service\Annotation as PM;
use PureMachine\Bundle\WebServiceBundle\Exception\WebServiceException;
use PureMachine\Bundle\SDKBundle\Exception\Exception as PBException;
use PureMachine\Bundle\SDKBundle\Service\WebServiceClient;
use PureMachine\Bundle\SDKBundle\Store\Base\StoreHelper;
use PureMachine\Bundle\WebServiceBundle\WebService\BaseWebService;
use PureMachine\Bundle\WebServiceBundle\Event\WebServiceCalledServerEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use PureMachine\Bundle\WebServiceBundle\Service\Response\Renderer\IRenderer;
use PureMachine\Bundle\WebServiceBundle\Service\Response\Renderer\JsonRenderer;
use PureMachine\Bundle\WebServiceBundle\Service\Response\Renderer\JsonpRenderer;

class WebServiceManager extends WebServiceClient
{
    const ACCESS_LEVEL_PUBLIC = 'public';
    const ACCESS_LEVEL_PRIVATE = 'private';

    protected $webServices = null;
    protected $trace = false;
    protected $callStart = 0;
    protected $traceStack = array();
    protected $eventMetadata = array();
    protected $isJsonP = false;

    /**
     * @var IRenderer
     */
    protected $responseRenderer = null;

    public function __construct()
    {
        $this->webServices = array();
    }

    public function setContainer($container)
    {
        $this->symfonyContainer = $container;
        if ($container->hasParameter('trace')) {
            $this->trace = (boolean) $container->getParameter('trace');
        } else {
            $this->trace = false;
        }
    }

    public function addMetadata($key, $value)
    {
        $this->eventMetadata[$key] = $value;
    }

    public function trace($message, $shift=0)
    {
        if (!$this->trace) return;

        $time = number_format(round(microtime(true) - $this->callStart, 3) , 3);
        $data = array('time' => $time, 'message' => $message);

        /**
         * get caller
         */
        $bt = debug_backtrace(null,5);
        $caller = $bt[$shift];
        $data['line'] = $caller['line'];
        $data['file'] = $caller['file'];
        $data['class'] = $caller['class'];

        $caller = $bt[$shift+1];
        $data['function'] = $caller['function'];

        $this->traceStack[] = $data;
    }

    public function getSchema($ws)
    {
        if (array_key_exists(strtolower($ws), $this->webServices))
            return $this->webServices[strtolower($ws)];

        return null;
    }

    public function getSchemaVersions($ws)
    {
        $schemaDef = $this->getSchema($ws);

        return array_values(array_diff(array_keys($schemaDef), array('name')));
    }

    public function getSchemas()
    {
        return $this->webServices;
    }

    public function addServices($serviceIdAndobject)
    {
        /**
         * Check for cache first
         */
        $cache = new PhpFileCache(StoreManager::getCacheDirectory(), ".services-chemas.php");
        if ($cache->contains("service-schemas")) {
            $this->webServices = $cache->fetch("service-schemas");
            return;
        }

        foreach ($serviceIdAndobject as $data) {
            $this->addService($data['id'], $data['object']);
        }

        $cache->save("service-schemas", $this->webServices);
    }
    /**
     * Add a service instance that contains webServices
     *
     * @param string Symfony service id
     * @param \PureMachine\Bundle\WebServiceBundle\WebService\BaseWebService $object
     */
    public function addService($serviceId, $object)
    {
        if (!$object instanceof BaseWebService) return;
        if (!$serviceId) return;

        $rClass = new \ReflectionClass(get_class($object));
        $methods = $rClass->getMethods(\ReflectionMethod::IS_PUBLIC);
        $ar = $this->getAnnotationReader();

        //Find the default namespace
        $daClass = 'PureMachine\Bundle\WebServiceBundle\Service\Annotation\WSNamespace';
        $nsAnnotation = $ar->getClassAnnotation($rClass, $daClass);
        if ($nsAnnotation) $defaultNamespace = $nsAnnotation->value;
        else $nsAnnotation = '';

        foreach ($methods as $method) {
            /*
             * If the method has no Webservice annotation , exclude it from
             * the addService building
             */
            $webserviceAnnotationsClass = 'PureMachine\Bundle\WebServiceBundle\Service\Annotation\WebService';
            $wsAnnotations = $ar->getMethodAnnotation($method, $webserviceAnnotationsClass);
            if(is_null($wsAnnotations)) continue;

            //Lookup the namespace
            $nsAnnotation = $ar->getMethodAnnotation($method, $daClass);
            if ($nsAnnotation) $namespace = $nsAnnotation->value;
            else $namespace = $defaultNamespace;

            $annotations = $ar->getMethodAnnotations($method);
            $definition = array();
            $definition['inputType'] = 'object';
            $definition['inputClass'] = array();
            $definition['returnType'] = 'object';
            $definition['returnClass'] = array();
            $definition['headers'] = array();
            $description = '';
            $internal = array();
            $name = null;
            $version = null;

            foreach ($annotations as $annotation) {

                if ($annotation instanceof PM\WebService) {
                    $name = $namespace . "/" . $annotation->value;
                    $version = $annotation->version;
                    $definition['accessLevel'] = static::ACCESS_LEVEL_PRIVATE;
                    $internal['id'] = $serviceId;
                    $internal['method'] = $method->getName();
                } elseif ($annotation instanceof PM\ReturnClass) {
                    $definition['returnClass'] = $annotation->getValue();
                    if ($annotation->isArray) $definition['returnType'] = 'array';
                } elseif ($annotation instanceof PM\InputClass) {
                    $definition['inputClass'] = $annotation->getValue();
                    if ($annotation->isArray) $definition['inputType'] = 'array';
                } elseif ($annotation instanceof PM\Doc) {
                    $description = $annotation->description;
                } elseif ($annotation instanceof PM\Headers) {
                    if (is_array($annotation->value)) {
                        foreach ($annotation->value as $key => $value) {
                            $definition['headers'][$key] = $value;
                        }
                    }
                }

            }

            if (count($definition['returnClass']) == 0)
                throw new WebServiceException("A store class must be defined in returnClass "
                                             ."annotation",
                                              WebServiceException::WS_006);

            if ($name && $version) {
                $key = strtolower($name);

                if (!array_key_exists($key, $this->webServices)) {
                    $this->webServices[$key] = array();
                }

                $this->webServices[$key]['name'] = $name;
                $this->webServices[$key][$version] = array();
                $this->webServices[$key][$version]['definition'] = $definition;
                $this->webServices[$key][$version]['_internal'] = $internal;
                $this->webServices[$key]['description'] = $description;
            }
        }
    }

    /**
     * Creates the response renderer. Its decision is based on the
     * value of the isJsonP internal class variable.
     */
    protected function factoryRenderer()
    {
        if ($this->isJsonP) {
            return new JsonpRenderer();
        }
        return new JsonRenderer();
    }

    public function route(Request $request, $webServiceName,
                          $version=PM\WebService::DEFAULT_VERSION)
    {
        $callStart = microtime(true);
        $this->callStart = $callStart;
        $this->trackStack = array();
        $this->eventMetadata = array();
        $this->trace("start $webServiceName call");
        $response = null;

        $schema = $this->getSchema($webServiceName);
        if (!$schema) {
            throw new NotFoundHttpException("webService $webServiceName not found");
        }

        $inputData = $this->RequestToInputParams($request);

        //Detecting JSONP
        $this->isJsonP = $this->isJsonPRequest($request);

        //Create the response renderer
        $this->responseRenderer = $this->factoryRenderer();

        $url = $request->getSchemeAndHttpHost() . $request->getRequestUri();
        $method = $request->getMethod();

        try{
            $event = $this->logCalling($webServiceName, $inputData, $version, $url, $method);
        } catch (\Exception $e) {
            $this->logExceptionToFile($e);
            $response = $this->buildErrorResponse($webServiceName, $version, $e, false);
            $event = null;
        }

        if (is_null($response)) {
            $response = $this->localCall($webServiceName, $inputData, $version, false);
            if ($response->isStoreProperty('local')) {
                $response->setLocal(false);
            }
        }

        $statusCode = 200;
        if ($response->getStatus() == 'error' &&
            $response->getAnswer()->getCode() === WebServiceException::WS_002) {
            $statusCode = 404;
        }
        
        //Serialize output data.
        try {
            if ($version == 'V3') {
                $response = StoreHelper::serialize($response, false, false, true, true);
            } else {
                $response = StoreHelper::serialize($response);
            }
        } catch (\Exception $e) {
                $response = $this->buildErrorResponse($webServiceName, $version, $e, false);
        }

        $duration = round(microtime(true) - $callStart, 3);
        $this->trace("end $webServiceName call");
        $response = $this->logCalled($event, $response, $duration, $statusCode);


        $symfonyResponse = new Response();
        $symfonyResponse->setStatusCode($statusCode);

        if ($this->getContainer()->get('kernel')->getEnvironment() != 'prod' &&
            $request->query->has('debug')) {

            $content = "<html><body>" . $this->responseRenderer->render($webServiceName, $response) ."</body></html>";
            $symfonyResponse->setContent($content);

        } else {

            $this->responseRenderer->applyHeaders($symfonyResponse->headers);
            $symfonyResponse->setContent($this->responseRenderer->render($webServiceName, $response));
        }

        /**
         * Add headers if there is any
         */
        if (array_key_exists($version, $schema)) {
            foreach ($schema[$version]['definition']['headers'] as $key => $value) {
                $symfonyResponse->headers->set($key, $value);
            }
        }

        return $symfonyResponse;
    }

    public function logCalling($webServiceName, $inputData, $version, $url, $method)
    {
        /**
         * Trigger calling event
         */
        $event = new WebServiceCalledServerEvent($webServiceName, $inputData, null, $version,
            $url, $method, false, -1);
        $eventDispatcher = $this->symfonyContainer->get("event_dispatcher");
        $response = null;
        $eventDispatcher->dispatch("puremachine.webservice.server.calling", $event);

        return $event;
    }

    public function logCalled($event, $response, $duration, $httpStatusCode)
    {
        /**
         * Trigger called event
         */
        $event->setOutputData($response);
        $event->setHttpAnswerCode($httpStatusCode);
        $eventDispatcher = $this->symfonyContainer->get("event_dispatcher");

        $event->mergeMetadata($this->eventMetadata);
        $event->setMetadataValue('duration', $duration);
        $event->setMetadataValue('traceStack', $this->traceStack);

        try {
            $eventDispatcher->dispatch("puremachine.webservice.server.called", $event);
        } catch(\Exception $e) {
            $this->logExceptionToFile($e);
        }

        /**
         * Answer can be modified by listener
         */
        if ($event->getRefreshOutputData()) {
            $response = $event->getOutputData();
            $event->setRefreshOutputData(false);
        }

        /**
         * Set the Error Ticket ID if any
         */
        if ($event->getTicket()) {
            if ($response->status == 'error') {
                $response->answer->ticket = $event->getTicket();
            }
            $response->ticket = $event->getTicket();
        }

        return $response;
    }

    /**
     * Detects if the call if a JSONP call. Force to be only on GET method
     * Checks if the parameter "jsonp" exists and is a true boolean (or a "true" string
     * due to issue with casting on parameter conversion)
     *
     * @param Request $request
     * @return bool
     */
    private function isJsonPRequest(Request $request)
    {
        $method = $request->getMethod();
        if($method!="GET") return false; //JSONP is forced to be on GET requests only
        $parameters = $this->findRequestParameters($request);

        if(array_key_exists("jsonp", $parameters)) {
            $jsonpValue = $parameters["jsonp"];
            if (($jsonpValue===true) || ($jsonpValue==="true")) {
                return true;
            }
        }

        return false;
    }

    /**
     * Search the parameters with IE8 compatibility
     *
     * @param Request $request
     */
    private function findRequestParameters(Request $request)
    {
        //We get parameters from POST or GET
        $parameters = array_merge($request->query->all(), $request->request->all());

        //We merge raw data if there is any
        //Need for Internet Explorer 8 ajax calls
        if ($request->getContent()) {
            parse_str($request->getContent(), $body_data);
            $firstKey = key($body_data);
            reset($body_data);
            if (is_array($body_data) && $body_data[$firstKey] != "") {
                $parameters = array_merge($parameters, $body_data);
            }
            else {
                $body_data = $this->jsonDecode($request->getContent(), true);
                if (is_array($body_data)) {
                    $parameters = array_merge($parameters, $body_data);
                }
            }
        }

        return $parameters;
    }

    protected function jsonDecode($json, $assoc=false)
    {
        return json_decode($json, $assoc);
    }

    private function RequestToInputParams(Request $request)
    {
        $parameters = $this->findRequestParameters($request);

        //first, we check if we are a object in JSON inside the parameter
        if (array_key_exists('json', $parameters)) {
            $inputValues = $this->jsonDecode($parameters['json']);
            if ($inputValues) return $inputValues;
        }

        if (count($parameters) == 0) return null;

        /**
         * Convert string to integer if possible
         */
        foreach ($parameters as $key => $value) {
            if (is_numeric($value)) {
                $floatValue = floatval($value);
                $intValue = intval($value);

                if ($intValue == $floatValue) {
                    $parameters[$key] = $intValue;
                } else {
                    $parameters[$key] = $floatValue;
                }
            }
        }

        return (object) $parameters;
    }

    public function localCall($webServiceName, $inputData, $version, $triggerEvent=true)
    {
        $response = null;

        if ($triggerEvent) {

            $callStart = microtime(true);
            $this->callStart = $callStart;
            $this->trackStack = array();
            $this->eventMetadata = array();
            $this->trace("start $webServiceName call");

            if ($inputData instanceof BaseStore) {
                $intput = $inputData->serialize();
            } else {
                $intput = $inputData;
            }
            $event = new WebServiceCalledServerEvent($webServiceName, $intput, null, $version,
                null, null, true, -1);
            $eventDispatcher = $this->symfonyContainer->get("event_dispatcher");

            try{
                $eventDispatcher->dispatch("puremachine.webservice.server.calling", $event);
            } catch (\Exception $e) {
                $this->logExceptionToFile($e);
                $response = $this->buildErrorResponse($webServiceName, $version, $e, false);
            }
        }

        if (is_null($response)) {
            $response = $this->localCallImplementation($webServiceName, $inputData, $version);
        }

        /**
         * Trigger event
         */
        if ($triggerEvent) {
            $statusCode = -1;

            if ($response instanceof StoreResponse) {
                if ($response->getStatus() == 'success') {
                    $statusCode = 200;
                } else {
                    $statusCode = 500;
                }
            }

            if ($response instanceof BaseStore) {
                $outputData = $response->serialize();
            } else {
                $outputData = $response;
            }

            $event->setOutputData($outputData);
            $event->setHttpAnswerCode($statusCode);
            $eventDispatcher = $this->symfonyContainer->get("event_dispatcher");

            $duration = round((microtime(true) - $callStart), 3);
            $this->trace("end $webServiceName call");
            $event->mergeMetadata($this->eventMetadata);
            $event->setMetadataValue('duration', $duration);
            $event->setMetadataValue('traceStack', $this->traceStack);

            try {
                $eventDispatcher->dispatch("puremachine.webservice.server.called", $event);
            } catch(\Exception $e) {
                $this->logExceptionToFile($e);
            }


            if ($event->getRefreshOutputData()) {
                $response = StoreHelper::unSerialize($event->getOutputData(), array());
                $event->setRefreshOutputData(false);
            }

            /**
             * Set the Error Ticket ID if any
             */
            if ($event->getTicket()) {
                if ($response->getStatus() == 'error') {
                    $response->getAnswer()->setTicket($event->getTicket());
                }
                $response->setTicket($event->getTicket());
            }
        }

        return $response;
    }

    protected function _simpleTypeToStore($inputData)
    {
        return StoreHelper::simpleTypeToStore($inputData);
    }

    protected function localCallImplementation($webServiceName, $inputData, $version)
    {
        //Handle special mapping :
        //Simple type are mapped to Store classes
        $inputData = $this->_simpleTypeToStore($inputData);

        //Try to lookup The schema
        try {
        $schema = $this->lookupLocalWebService($webServiceName, $version);
        } catch (\Exception $e) {
            return $this->buildErrorResponse($webServiceName, $version, $e);
        }

        //Cast $inputValue if needed
        try {
            $classNames = $schema['definition']['inputClass'];
            $inputData = StoreHelper::unSerialize($inputData, $classNames,
                                                  $this->getAnnotationReader(),
                                                  $this->getContainer());
        } catch (\Exception $e) {
            return $this->buildErrorResponse($webServiceName, $version, $e);
        }

        //Validate input value
        try {
            $this->checkType($inputData, $schema['definition']['inputType'],
                             $schema['definition']['inputClass'],
                             WebServiceException::WS_003);
        } catch (\Exception $e) {
            if ($e instanceof PBException) {
                $this->copyMessageToMerchantMessage($e);
            }
                return $this->buildErrorResponse($webServiceName, $version, $e);
        }

        $method = $schema['_internal']['method'];
        try {
            $response = $this->symfonyContainer->get($schema['_internal']['id'])->$method($inputData);
        } catch (\Exception $e) {
            return $this->buildErrorResponse($webServiceName, $version, $e);
        }

        //Validate the output values
        try {
            $this->checkType($response, $schema['definition']['returnType'],
                             $schema['definition']['returnClass'],
                             WebServiceException::WS_004);
        } catch (\Exception $e) {
                return $this->buildErrorResponse($webServiceName, $version, $e);
        }

        //Everything good ! return the response
        return $this->buildResponse($webServiceName, $version, $response);
    }

    protected function copyMessageToMerchantMessage($e)
    {
        if ($e instanceof PBException) {
            $e->setMerchantDetails($e->getMessage());
        }
    }

    private function lookupLocalWebService($webServiceName, $version)
    {
        $key = strtolower($webServiceName);

        //Check if the webService Exists
        if (!array_key_exists($key, $this->webServices)) {
                throw new WebServiceException("Webservice '$webServiceName' not found",
                                             WebServiceException::WS_002);
        }

        //Check if the version exists
        if (!array_key_exists($version, $this->webServices[$key])) {
                throw new WebServiceException("version '$version' does not exists for "
                                             ."webservice '$webServiceName'",
                                            WebServiceException::WS_002);
        }

        //Got the schema !!
        return $this->webServices[$key][$version];
    }

    /**
     * Adding Application version if defined
     */
    public function buildResponse($webServiceName, $version, $data, $fullUrl=null, $status='success')
    {
        $response = parent::buildResponse($webServiceName, $version, $data, $fullUrl, $status);

        if ($this->getContainer()->hasParameter('applicationVersion')) {
            $response->setApplicationVersion($this->getContainer()->getParameter('applicationVersion'));
        }

        return $response;
    }

    public function logExceptionToFile($e)
    {
        $message = '"' . $e->getMessage() . '" at ' . $e->getFile() . ":" . $e->getLine();
        $className = get_class($e);
        $this->symfonyContainer->get('logger')->critical("logging failed with $className: " . $message);
    }
}
