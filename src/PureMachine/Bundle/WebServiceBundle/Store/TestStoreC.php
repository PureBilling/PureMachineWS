<?php
namespace PureMachine\Bundle\WebServiceBundle\Store;

use Symfony\Component\Validator\Constraints as Assert;
use PureMachine\Bundle\SDKBundle\Store\Annotation as Store;
use PureMachine\Bundle\SDKBundle\Store\Base\BaseStore;

class TestStoreC extends BaseStore
{
    /**
     * @Store\Property(description="Exception generic message")
     * @Assert\Type("string");
     * @Assert\NotBlank
     */
    protected $testString;
}
