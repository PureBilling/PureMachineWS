<?php
namespace PureMachine\Bundle\WebServiceBundle\Service\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("METHOD")
 */
class WebService extends Annotation
{
    const DEFAULT_VERSION = 'V1';

    /** @var string @Required */
    public $value;

    public $version = self::DEFAULT_VERSION;
}
