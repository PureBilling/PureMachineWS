<?php
namespace PureMachine\Bundle\WebServiceBundle\WebService;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;
use Symfony\Component\DependencyInjection\ContainerInterface;
use JMS\DiExtraBundle\Annotation\Service;// new Service() // PHP Bug

class SymfonyBaseWebService extends BaseWebService implements ContainerAwareInterface
{
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
