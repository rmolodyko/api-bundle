<?php declare(strict_types=1);

namespace Satori\Api\Lib;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMInvalidArgumentException;
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

    /** @var mixed */
    protected $entity;

    /**
     * Serializer constructor.
     *
     * @param EntityManager $entityManager
     * @param ValidatorInterface $validator
     * @param EntityContext $context
     */
    public function __construct(EntityManager $entityManager, ValidatorInterface $validator, EntityContext $context)
    {
        $this->validator = $validator;
        $this->entityManager = $entityManager;
        $this->entityContext = $context;
    }

    /**
     * Fill entities by data
     *
     * @param $data
     * @param $className
     * @return array
     * @throws \InvalidArgumentException
     * @throws \ReflectionException
     * @throws MappingException
     */
    public function fill($data, $className)
    {
        $errors = [];

        /** @var ClassMetadata $metadata */
        $metadata = $this->entityManager->getMetadataFactory()->getMetadataFor($className);
        $id = [];
        // Get ids of entity
        foreach ($metadata->getIdentifierFieldNames() as $identifier) {
            if (!array_key_exists($identifier, $data)) {
                throw new \InvalidArgumentException('Missing identifier in data');
            }
            $id[$identifier] = $data[$identifier];
            // Remove id from data for correct instantiating the field values
            unset($data[$identifier]);
        }

        // Find entity
        $entity = $this->entityManager->getRepository($metadata->getName())->findOneBy($id);

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
            if (array_key_exists($fieldName, $data) || $this->hasRelationField($fieldName)) {
                $className = $metadata->getAssociationTargetClass($fieldName);
                foreach ($this->relationField($fieldName) as $dataFieldName) {
                    $childErrors = $this->fill($data[$dataFieldName], $className);
                    if (count($childErrors)) {
                        $errors[$fieldName] = $childErrors;
                    }
                }
            }
        }

        // Get errors
        return array_merge($errors, $this->validateEntity($entity));
    }

    protected function relationField($fieldName)
    {
        $relations = $this->getEntityContext()->getRelations();
        if (array_key_exists($fieldName, $relations)) {
            return $relations[$fieldName];
        }
        return [$fieldName];
    }

    protected function hasRelationField($fieldName)
    {
        return array_key_exists($fieldName, $this->getEntityContext()->getRelations());
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
        // Fill entity
        $errors = $this->fill($data, $className);

        if ($this->getEntityContext()->isThrowExceptionOnError()) {
            // Check errors
            $this->error(count($errors) === 0, $errors);
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
