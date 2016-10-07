<?php declare(strict_types=1);

namespace Satori\Api\Lib\Serializer;

use Doctrine\ORM\EntityManager;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer as JMSSerializer;

/**
 * Class Serializer
 *
 * @author Ruslan Molodyko <molodyko@samsonos.com>
 */
class Serializer
{
    /** @var JMSSerializer */
    protected $jmsSerializer;

    /** @var EntityManager */
    protected $entityManager;

    /**
     * Serializer constructor.
     *
     * @param JMSSerializer $serializer
     * @param EntityManager $entityManager
     */
    public function __construct(JMSSerializer $serializer, EntityManager $entityManager)
    {
        $this->jmsSerializer = $serializer;
        $this->entityManager = $entityManager;
    }

    /**
     * Get array representations of entity(ies)
     *
     * @param $data
     * @param array $groups
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function toArray($data, array $groups)
    {
        // This is the context you created in your code above when serializing by hand
        $context = SerializationContext::create()->setGroups($groups);
        // Show fields with null values
        $context->setSerializeNull(true);
        return $this->jmsSerializer->toArray($data, $context);
    }
}
