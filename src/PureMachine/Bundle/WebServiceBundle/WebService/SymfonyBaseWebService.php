<?php
namespace PureMachine\Bundle\WebServiceBundle\WebService;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;
use Symfony\Component\DependencyInjection\ContainerInterface;
use JMS\DiExtraBundle\Annotation\Service;// new Service() // PHP Bug

class SymfonyBaseWebService extends BaseWebService implements ContainerAwareInterface
{
    private function neverUsed()
    {
        /*
         * PHP 5.3.3 CentOS bug : some  include are needed for children annotation classes
         * I create a annotation object for nothing here only to avoid code optimizer to
         * remote the use class
         */
        new Service();
    }

    /**
     * @var ContainerInterface
     */
    protected $container = null;

    /**
     * @InjectParams({
     *     "container" = @Inject("service_container")
     * })
     * @param  ContainerInterface $container
     * @return void
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Returns the symfony application container
     *
     * @return ContainerInteface
     */
    protected function getContainer()
    {
        return $this->container;
    }

    protected function get($service)
    {
        return $this->getContainer()->get($service);
    }

    protected function getManager()
    {
        return $this->getContainer()->get('doctrine.orm.entity_manager');
    }

    protected function getEntityManager()
    {
        return $this->getManager();
    }

    protected function getRepository($repo)
    {
        return $this->getContainer()->get('doctrine.orm.entity_manager')->getRepository($repo);
    }

}
