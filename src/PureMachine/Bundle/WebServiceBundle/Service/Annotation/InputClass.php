<?php
namespace PureMachine\Bundle\WebServiceBundle\Service\Annotation;

use Doctrine\Common\Annotations\Annotation;

use PureMachine\Bundle\WebServiceBundle\Exception\WebServiceException;

/**
 * @Annotation
 * @Target("METHOD")
 */
class InputClass extends Annotation
{
    /** @var string @Required */
    public $classes;

    /** @var boolean */
    public $isArray = false;

    public function getValue()
    {
        if ($this->value) $classes = (array) $this->value;
        else $classes = (array) $this->classes;

         if (count($classes)==0)
                throw new WebServiceException("there is no class defined in InputClass "
                                             ."or ReturnClass ",
                                             WebServiceException::WS_101);

        foreach ($classes as $class)
            if (!class_exists($class))
                throw new WebServiceException("Class defined in InputClass or ReturnClass "
                                             ."does not exists : " . $class,
                                             WebServiceException::WS_101);

        return $classes;
    }
}
