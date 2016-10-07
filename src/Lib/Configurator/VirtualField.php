<?php declare(strict_types=1);

namespace Satori\Api\Lib\Configurator;

use Satori\Api\Lib\EntityContext;

/**
 * Class VirtualField
 *
 * @author Ruslan Molodyko <molodyko@samsonos.com>
 */
class VirtualField
{
    /** @var EntityContext */
    protected $entityContext;

    /**
     * @param $fieldName
     * @return array
     */
    public function relationField(string $fieldName): array
    {
        $relations = $this->getEntityContext()->getRelations();
        if (array_key_exists($fieldName, $relations)) {
            return $relations[$fieldName];
        }
        return [$fieldName];
    }

    /**
     * @param $fieldName
     * @return bool
     */
    public function hasRelationField(string $fieldName): bool
    {
        return array_key_exists($fieldName, $this->getEntityContext()->getRelations());
    }

    /**
     * @return EntityContext
     */
    public function getEntityContext(): EntityContext
    {
        return $this->entityContext;
    }

    /**
     * @param EntityContext $entityContext
     * @return VirtualField
     */
    public function setEntityContext(EntityContext $entityContext): VirtualField
    {
        $this->entityContext = $entityContext;

        return $this;
    }
}
