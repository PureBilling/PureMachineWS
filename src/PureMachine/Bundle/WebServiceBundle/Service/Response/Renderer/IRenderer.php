<?php
namespace PureMachine\Bundle\WebServiceBundle\Service\Response\Renderer;

use Symfony\Component\HttpFoundation\ResponseHeaderBag;

interface IRenderer
{

    public function render($webservice, $content);
    public function applyHeaders(ResponseHeaderBag $header);

}
