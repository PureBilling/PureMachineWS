<?php
namespace PureMachine\Bundle\WebServiceBundle\Service\Response\Renderer;

use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class JsonRenderer implements IRenderer
{

    public function render($webservice, $content)
    {
        return json_encode($content);
    }

    public function applyHeaders(ResponseHeaderBag $header)
    {
        $header->set('Content-Type', 'application/json; charset=utf-8');
    }

}
