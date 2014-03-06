<?php

namespace PureMachine\Bundle\WebServiceBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use PureMachine\Bundle\SDKBundle\Store\Base\BaseStore;

/**
 * Event dispatched after the local or remote call
 * is executed
 */
class WebServiceCalledServerEvent extends Event
{
    /**
     * @var string
     */
    private $webServiceName;

    /**
     * @var BaseStore
     */
    private $inputData;

    /**
     * @var BaseStore
     */
    private $outputData;

    /**
     * @var string
     */
    private $version;

    /**
     * @var string
     */
    private $originalUrl;

    /**
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $local;

    /**
     * @var integer
     */
    private $httpAnswerCode;

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
     * Return outputData
     *
     * @return BaseStore
     */
    public function getInputData()
    {
        return $this->inputData;
    }

    /**
     * Return outputData
     *
     * @return BaseStore
     */
    public function getOutputData()
    {
        return $this->outputData;
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

    /**
     * @return string
     */
    public function getOriginalUrl()
    {
        return $this->originalUrl;
    }

    /**
     * HTTP method used : GET or POST
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    public function getLocal()
    {
        return $this->local;
    }

    /**
     * @return integer
     */
    public function getHttpAnswerCode()
    {
        return $this->httpAnswerCode;
    }
}
