<?php
namespace PureMachine\Bundle\WebServiceBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class WebServiceController extends Controller
{
    /**
     * Public route.
     * Only webService defined a public comes here.
     *
     * @Route("/{version}/{name}", requirements={"name"=".+", "accessLevel"="ws|private"})
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function webServiceAction(Request $request, $version, $name)
    {
       return $this->container
                   ->get('pureMachine.sdk.webServiceManager')
                   ->route($request, $name, $version);
    }
}
