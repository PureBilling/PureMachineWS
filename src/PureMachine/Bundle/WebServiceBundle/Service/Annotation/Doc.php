<?php
namespace PureMachine\Bundle\WebServiceBundle\Service\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("METHOD")
 */
class Doc extends Annotation
{
    /** @var string */
    public $description = "";
}
