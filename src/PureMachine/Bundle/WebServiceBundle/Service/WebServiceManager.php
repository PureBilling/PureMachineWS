<?php
namespace PureMachine\Bundle\WebServiceBundle\Service;

use JMS\DiExtraBundle\Annotation\Service;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;

use PureMachine\Bundle\SDKBundle\Store\Base\BaseStore;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use PureMachine\Bundle\SDKBundle\Store\WebService\Response as StoreResponse;
use PureMachine\Bundle\WebServiceBundle\Service\Annotation as PM;
use PureMachine\Bundle\WebServiceBundle\Exception\WebServiceException;
use PureMachine\Bundle\SDKBundle\Exception\Exception;
use PureMachine\Bundle\SDKBundle\Service\WebServiceClient;
use PureMachine\Bundle\SDKBundle\Store\Base\StoreHelper;
use PureMachine\Bundle\WebServiceBundle\WebService\BaseWebService;
use PureMachine\Bundle\WebServiceBundle\Event\WebServiceCalledServerEvent;

/**
 * @Service("pureMachine.sdk.webServiceManager")
 */
class WebServiceManager extends WebServiceClient implements ContainerAwareInterface
{
    const ACCESS_LEVEL_PUBLIC = 'public';
    const ACCESS_LEVEL_PRIVATE = 'private';

    protected $webServices = null;

    public function __construct()
    {
        $this->webServices = array();
    }

    /**
     * @InjectParams({
     *     "container" = @Inject("service_container")
     * })
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
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
                }
            }

            if (count($definition['returnClass']) == 0)
                throw new WebServiceException("A store class must be defined in returnClass "
                                             ."annotation",
                                              WebServiceException::WS_006);

            if ($name && $version) {
                $key = strtolower($name);
                $this->webServices[$key] = array();
                $this->webServices[$key]['name'] = $name;
                $this->webServices[$key][$version] = array();
                $this->webServices[$key][$version]['definition'] = $definition;
                $this->webServices[$key][$version]['_internal'] = $internal;
                $this->webServices[$key]['description'] = $description;
            }
        }
    }

    public function route(Request $request, $webServiceName,
                          $version=PM\WebService::DEFAULT_VERSION)
    {
        $inputData = $this->RequestToInputParams($request);

        /**
         * Trigger calling event
         */
        $url = $request->getSchemeAndHttpHost() . $request->getRequestUri();
        $event = new WebServiceCalledServerEvent($webServiceName, $inputData, null, $version,
            $url, $request->getMethod(), false, -1);
        $eventDispatcher = $this->container->get("event_dispatcher");
        $eventDispatcher->dispatch("puremachine.webservice.server.calling", $event);

        $response = $this->localCall($webServiceName, $inputData, $version, false);
        $response->setLocal(false);

        //Serialize output data.
        try {
            $response = StoreHelper::serialize($response);
        } catch (Exception $e) {
                $response = $this->buildErrorResponse($webServiceName, $version, $e, false);
        }

        $symfonyResponse = new Response();

        if ($response->status == 'error' &&
            $response->answer->code == WebServiceException::WS_002)
            $symfonyResponse->setStatusCode(404);

        $symfonyResponse->headers->set('Content-Type', 'application/json; charset=utf-8');
        $symfonyResponse->setContent(json_encode($response));

        /**
         * Trigger called event
         */
        $event->setOutputData($response);
        $event->setHttpAnswerCode($symfonyResponse->getStatusCode());
        $eventDispatcher = $this->container->get("event_dispatcher");
        $eventDispatcher->dispatch("puremachine.webservice.server.called", $event);

        return $symfonyResponse;
    }

    private function RequestToInputParams(Request $request)
    {
        //We get parameters from POST or get
        $parameters = array_merge($request->query->all(), $request->request->all());

        //first, we check if we are a object in JSON inside the parameter
        if (array_key_exists('json', $parameters)) {
            $inputValues = json_decode($parameters['json']);
            if ($inputValues) return $inputValues;
        }

        if (count($parameters) == 0) return null;
        return (object) $parameters;
    }

    public function localCall($webServiceName, $inputData, $version, $triggerEvent=true)
    {
        if ($triggerEvent) {
            if ($inputData instanceof BaseStore) {
                $intput = $inputData->serialize();
            } else {
                $intput = $inputData;
            }
            $event = new WebServiceCalledServerEvent($webServiceName, $intput, null, $version,
                null, null, true, -1);
            $eventDispatcher = $this->container->get("event_dispatcher");
            $eventDispatcher->dispatch("puremachine.webservice.server.calling", $event);
        }

        $response = $this->localCallImplementation($webServiceName, $inputData, $version);

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
            $eventDispatcher = $this->container->get("event_dispatcher");
            $eventDispatcher->dispatch("puremachine.webservice.server.called", $event);
        }

        return $response;
    }

    protected function localCallImplementation($webServiceName, $inputData, $version)
    {
        //Handle special mapping :
        //Simple type are mapped to Store classes
        $inputData = StoreHelper::simpleTypeToStore($inputData);

        //Try to lookup The schema
        try {
        $schema = $this->lookupLocalWebService($webServiceName, $version);
        } catch (Exception $e) {
            return $this->buildErrorResponse($webServiceName, $version, $e);
        }

        //Cast $inputValue if needed
        try {
            $classNames = $schema['definition']['inputClass'];
            $inputData = StoreHelper::unSerialize($inputData, $classNames,
                                                  $this->getAnnotationReader(),
                                                  $this->getContainer());
        } catch (Exception $e) {
            return $this->buildErrorResponse($webServiceName, $version, $e);
        }

        //Validate input value
        try {
            $this->checkType($inputData, $schema['definition']['inputType'],
                             $schema['definition']['inputClass'],
                             WebServiceException::WS_003);
        } catch (Exception $e) {
                return $this->buildErrorResponse($webServiceName, $version, $e);
        }

        $method = $schema['_internal']['method'];
        try {
            $response = $this->container->get($schema['_internal']['id'])->$method($inputData);
        } catch (Exception $e) {
            return $this->buildErrorResponse($webServiceName, $version, $e);
        }

        //Validate the output values
        try {
            $this->checkType($response, $schema['definition']['returnType'],
                             $schema['definition']['returnClass'],
                             WebServiceException::WS_004);
        } catch (Exception $e) {
                return $this->buildErrorResponse($webServiceName, $version, $e);
        }

        //Everything good ! return the response
        return $this->buildResponse($webServiceName, $version, $response);
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
}
