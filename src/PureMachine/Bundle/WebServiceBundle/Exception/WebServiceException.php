<?php
namespace PureMachine\Bundle\WebServiceBundle\Exception;

use PureMachine\Bundle\SDKBundle\Exception\WebServiceException as SDKWebServiceException;

class WebServiceException extends SDKWebServiceException
{
    const WS_101 = 'WS_101';
    const WS_101_MESSAGE = 'webService annotation error';
}
