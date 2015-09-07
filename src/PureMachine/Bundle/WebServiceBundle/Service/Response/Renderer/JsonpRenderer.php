<?php
namespace PureMachine\Bundle\WebServiceBundle\Service\Response\Renderer;

use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class JsonpRenderer extends JsonRenderer implements IRenderer
{

    public function render($webservice, $content)
    {
        $json = parent::render($webservice, $content);
        return "purebilling_jsonp('".$webservice."','".$json."');";
    }

    public function applyHeaders(ResponseHeaderBag $header)
    {
        $header->set('Content-Type', 'application/javascript; charset=utf-8');
    }

}
