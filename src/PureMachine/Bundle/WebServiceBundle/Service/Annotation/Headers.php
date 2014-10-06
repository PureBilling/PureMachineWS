<?php
namespace PureMachine\Bundle\WebServiceBundle\Service\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class Headers extends Annotation
{
    /** @var array @Required */
    public $value = array();
}
