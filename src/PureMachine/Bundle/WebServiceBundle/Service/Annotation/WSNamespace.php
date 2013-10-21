<?php
namespace PureMachine\Bundle\WebServiceBundle\Service\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class WSNamespace extends Annotation
{
    /** @var string @Required */
    public $value;
}
