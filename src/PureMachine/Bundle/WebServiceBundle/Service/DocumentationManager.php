<?php
namespace PureMachine\Bundle\WebServiceBundle\Service;

use JMS\DiExtraBundle\Annotation\Service;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;

use PureMachine\Bundle\SDKBundle\Store\Base\BaseStore;
use PureMachine\Bundle\SDKBundle\Store\WSDoc\LiteralPropertyDoc;
use PureMachine\Bundle\SDKBundle\Store\WSDoc\ObjectPropertyDoc;
use PureMachine\Bundle\SDKBundle\Store\WSDoc\WSInputOutputValueDoc;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use PureMachine\Bundle\SDKBundle\Store\WSDoc\WSReference;
use PureMachine\Bundle\SDKBundle\Store\WSDoc\StoreDoc;

/**
 * @Service("pure_machine.sdk.documentation_manager")
 */
class DocumentationManager implements ContainerAwareInterface
{
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

    public function getContainer()
    {
        return $this->container;
    }

    public function getWSReference($wsName, $version='V1')
    {
        $wsManager = $this->getContainer()->get('pureMachine.sdk.webServiceManager');
        $schema = $wsManager->getSchema($wsName);
        $def = $schema[$version]['definition'];

        $ref = new WSReference();
        $ref->setName($schema['name']);
        $ref->setDescription($schema['description']);

        //InputTypes
        $inputTypes = [];
        foreach ($def['inputClass'] as $inputClass) {
            $store = new WSInputOutputValueDoc();
            $store->setType($def['inputType']);
            $store->setClass($inputClass);
            $store->setName($this->getClassName($inputClass));
            $inputTypes[] = $this->getStoreDocumentation($store);
        }
        $ref->setInputTypes($inputTypes);

        //ReturnType
        $returnTypes = [];
        foreach ($def['returnClass'] as $returnClass) {
            $store = new WSInputOutputValueDoc();
            $store->setType($def['returnType']);
            $store->setClass($returnClass);
            $returnTypes[] = $this->getStoreDocumentation($store);
        }
        $ref->setReturnTypes($returnTypes);

        return $ref;
    }

    private function getClassName($class)
    {
        if (is_object($class)) $class = get_class($class);

        $class = explode('\\', $class);

        return end($class);
    }

    /**
     * Lookup Recursively of store children
     * @param  StoreDoc $store
     * @return StoreDoc
     */
    private function getStoreDocumentation($docStore)
    {
        $class = $docStore->getClass();

        if (!$class) {
            return $docStore;
        }

        if (!class_exists($class)) {
            return $docStore;
        }

        /**
         * Lookup The store in any
         */
        $docInstance = new $class();

        if (!$docInstance instanceof BaseStore) {
            return $docStore;
        }

        $storeSchema = $docInstance->getJsonSchema();

        $children = [];
        foreach ($storeSchema->definition as $name => $def) {

            //Ignore internal properties
            if (substr($name,0,1) == '_') {
                continue;
            }

            //We have a literal
            if (count($def->storeClasses) == 0) {
                $c = new LiteralPropertyDoc();
            }
            //We have a store
            else {
                $c = new ObjectPropertyDoc();

                $subChilds = [];
                foreach ($def->storeClasses as $storeClass) {
                    $propertyStore = new StoreDoc();
                    $propertyStore->setType($def->type);
                    $propertyStore->setClass($storeClass);
                    $subChilds[] = $this->getStoreDocumentation($propertyStore);
                }
                $c->setChildren($subChilds);
            }

            $c->setType($def->type);
            $c->setDescription($def->description);
            $c->setName($name);
            $c->setValidationConstraints($def->validationConstraints);

            //Set required flag
            foreach ($c->getValidationConstraints() as $constraint) {
                if ($constraint == 'NotBlank') {
                    $c->setRequired(true);
                }
            }

            $children[] = $c;
        }
        $docStore->setChildren($children);

        return $docStore;
    }
}
