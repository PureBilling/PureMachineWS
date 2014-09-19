<?php

namespace PureMachine\Bundle\WebServiceBundle\Event;

use PureMachine\Bundle\SDKBundle\Event\HttpRequestEvent;
use PureMachine\Bundle\SDKBundle\Store\Base\BaseStore;

/**
 * Event dispatched after the local or remote call
 * is executed
 */
class WebServiceCalledServerEvent extends HttpRequestEvent
{
    /**
     * @var string
     */
    private $webServiceName;

    /**
     * @var string
     */
    private $version;

    /**
     * @var string
     */
    private $local;

    /**
     * Class constructor
     *
     * @param string    $token
     * @param string    $webServiceName
     * @param BaseStore $outputData
     * @param string    $version
     * @param boolean   $local
     */
    public function __construct(
        $webServiceName,
        $inputData,
        $outputData,
        $version,
        $originalUrl,
        $method,
        $local,
        $httpAnswerCode
    )
    {
        $this->webServiceName = $webServiceName;
        $this->inputData = $inputData;
        $this->outputData = $outputData;
        $this->version = $version;
        $this->originalUrl = $originalUrl;
        $this->method = $method;
        $this->local = $local;
        $this->httpAnswerCode = $httpAnswerCode;
        $this->metadata = array();
    }

    /**
     * Return WebServiceName
     *
     * @return string
     */
    public function getWebServiceName()
    {
        return $this->webServiceName;
    }
    /**
     * Return version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    public function getLocal()
    {
        return $this->local;
    }
}
