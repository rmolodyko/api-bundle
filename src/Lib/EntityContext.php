<?php declare(strict_types=1);

namespace Satori\Api\Lib;

/**
 * Class EntityContext
 *
 * @author Ruslan Molodyko <molodyko@samsonos.com>
 */
class EntityContext
{
    /** @var bool True if entity has error and need throw exception otherwise not */
    protected $throwExceptionOnError = false;

    /** @var array */
    protected $relations = [];

    /**
     * @param boolean $throwExceptionOnError
     * @return EntityContext
     */
    public function setThrowExceptionOnError(bool $throwExceptionOnError): EntityContext
    {
        $this->throwExceptionOnError = $throwExceptionOnError;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isThrowExceptionOnError(): bool
    {
        return $this->throwExceptionOnError;
    }

    /**
     * @return array
     */
    public function getRelations(): array
    {
        return $this->relations;
    }

    /**
     * @param array $relations
     * @return EntityContext
     */
    public function setRelations(array $relations): EntityContext
    {
        $this->relations = $relations;

        return $this;
    }
}
