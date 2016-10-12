<?php declare(strict_types=1);

namespace Satori\Api\Lib\Configurator;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\ORM\PersistentCollection;
use Satori\Api\Lib\EntityContext;
use Satori\CatchException\Lib\CatchExceptionTrait;
use Satori\CatchException\Lib\Exception\CatchResponseException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class EntityConfigurator
 *
 * @author Ruslan Molodyko <molodyko@samsonos.com>
 */
class EntityConfigurator
{
    use CatchExceptionTrait;

    /** @var ValidatorInterface */
    protected $validator;

    /** @var EntityManager */
    protected $entityManager;

    /** @var EntityContext */
    protected $entityContext;

    /** @var VirtualField */
    protected $virtualField;

    /** @var mixed */
    protected $entity;

    /**
     * Serializer constructor.
     *
     * @param EntityManager $entityManager
     * @param ValidatorInterface $validator
     * @param EntityContext $context
     * @param VirtualField $virtualField
     */
    public function __construct(
        EntityManager $entityManager,
        ValidatorInterface $validator,
        EntityContext $context,
        VirtualField $virtualField
    ) {
        $this->validator = $validator;
        $this->entityManager = $entityManager;
        $this->entityContext = $context;
        $this->virtualField = $virtualField;

        $this->virtualField->setEntityContext($context);
    }

    /**
     * Fill entities by data
     *
     * @param $data
     * @param $className
     * @param boolean $create When need create the entity
     * @return array
     * @throws \InvalidArgumentException
     * @throws \ReflectionException
     * @throws MappingException
     */
    public function fill($data, $className, $create)
    {
        $errors = [];

        /** @var ClassMetadata $metadata */
        $metadata = $this->entityManager->getMetadataFactory()->getMetadataFor($className);

        // Find or create new entity
        $id = [];
        // Get ids of entity
        foreach ($metadata->getIdentifierFieldNames() as $identifier) {
            if (array_key_exists($identifier, $data)) {
                $id[$identifier] = $data[$identifier];
                // Remove id from data for correct instantiating the field values
                unset($data[$identifier]);
            }
        }

        if (!$create && count($id) > 0) {

            // Find entity
            $entity = $this->entityManager->getRepository($metadata->getName())->findOneBy($id);
        } else {
            $entity = new $className();
        }

        if (!$this->entity) {
            $this->entity = $entity;
        }

        // Iterate field names and set data value
        foreach ($metadata->getFieldNames() as $field) {
            // Add necessary checks about field read/write operation feasibility here
            if (array_key_exists($field, $data)) {
                // Setters are not being called! Inflection is up to you if you need it!
                $metadata->setFieldValue($entity, $field, $data[$field]);
            }
        }

        // Get related entities and set their data
        foreach ($metadata->getAssociationNames() as $fieldName) {
            if (
                count($data) &&
                (array_key_exists($fieldName, $data) || $this->virtualField->hasRelationField($fieldName))
                && !in_array($fieldName, $this->getEntityContext()->getDisableRelationList(), true)
            ) {
                $className = $metadata->getAssociationTargetClass($fieldName);
                /** @var ClassMetadata $metadataChild */
                $metadataChild = $this->entityManager->getMetadataFactory()->getMetadataFor($className);

                foreach ($this->virtualField->relationField($fieldName) as $dataFieldName) {

                    // Change link to another entity
                    // It possible when passed field name with id which not the same as before
                    // FIXME: Each request set new entity fix it for checking existing id
                    $ids = [];
                    foreach ($metadataChild->getIdentifierFieldNames() as $identifier) {
                        // Store primary columns passed from data
                        if (array_key_exists($identifier, $data[$dataFieldName])) {
                            $ids[$identifier] = $data[$dataFieldName][$identifier] ?: null;
                        }
                    }

                    // If need change link to entity find it and set into parent entity
                    if (count($ids)) {
                        $info = $typeRelation = $metadata->getAssociationMapping($fieldName);
                        $isMultiple = $info['type'] === ClassMetadataInfo::ONE_TO_MANY;
                        // Find entity
                        $entityChild = $this->entityManager
                            ->getRepository($metadataChild->getName())
                            ->findOneBy($ids);

                        $value = $entityChild;
                        if ($isMultiple) {
                            if (null === $value = $metadata->getFieldValue($entity, $fieldName)) {
                                continue;
                            }
                            if (is_array($value)) {
                                $value = array_merge($value, $entityChild);
                            } elseif ($value instanceof PersistentCollection) {
                                $value->add($entityChild);
                            }
                        }
                        $metadata->setFieldValue($entity, $fieldName, $value);
                    }

                    $childErrors = $this->fill($data[$dataFieldName], $className, $create);
                    if (count($childErrors)) {
                        $errors[$fieldName] = $childErrors;
                    }
                }
            }
        }

        // Get errors
        return array_merge($errors, $this->validateEntity($entity));
    }

    /**
     * Validate entity
     *
     * @param $entity
     * @return array
     */
    protected function validateEntity($entity): array
    {
        $list = [];
        $errors = $this->validator->validate($entity);

        // Check if error exists
        if (count($errors)) {
            /** @var ConstraintViolation $error */
            foreach ($errors as $error) {
                $list[$error->getPropertyPath()] = $error->getMessage();
            }
        }
        return $list;
    }

    /**
     * Get entity
     *
     * @return mixed
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Fill and save entity
     *
     * @param array $data
     * @param string $className
     * @return array
     * @throws ORMInvalidArgumentException
     * @throws OptimisticLockException
     * @throws CatchResponseException
     * @throws MappingException
     * @throws \InvalidArgumentException
     * @throws \ReflectionException
     */
    public function save(array $data, string $className): array
    {
        return $this->handle($data, $className, false);
    }

    /**
     * Fill and create entity
     *
     * @param array $data
     * @param string $className
     * @return array
     * @throws ORMInvalidArgumentException
     * @throws OptimisticLockException
     * @throws CatchResponseException
     * @throws MappingException
     * @throws \InvalidArgumentException
     * @throws \ReflectionException
     */
    public function create(array $data, string $className): array
    {
        return $this->handle($data, $className, true);
    }

    /**
     * Fill entity
     *
     * @param array $data De-Serialized data
     * @param string $className Class entity name
     * @param boolean $create Create new or find existing entity
     * @return array
     * @throws ORMInvalidArgumentException
     * @throws OptimisticLockException
     * @throws CatchResponseException
     * @throws MappingException
     * @throws \InvalidArgumentException
     * @throws \ReflectionException
     */
    protected function handle(array $data, string $className, bool $create)
    {
        $this->entity = null;

        // Fill entity
        $errors = $this->fill($data, $className, $create);

        if ($this->getEntityContext()->isThrowExceptionOnError()) {
            // Check errors
            $this->need(count($errors) === 0, $errors);
        }

        // Save
        $this->entityManager->persist($this->getEntity());
        $this->entityManager->flush();

        return $errors;
    }

    /**
     * @return EntityContext
     */
    public function getEntityContext(): EntityContext
    {
        return $this->entityContext;
    }
}
