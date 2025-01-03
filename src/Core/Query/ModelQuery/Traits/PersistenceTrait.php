<?php

namespace Gzhegow\Database\Core\Query\ModelQuery\Traits;

use Gzhegow\Database\Core\Orm;
use Gzhegow\Database\Package\Illuminate\Database\Eloquent\EloquentModelQueryBuilder;


/**
 * @mixin EloquentModelQueryBuilder
 */
trait PersistenceTrait
{
    /**
     * @return static
     */
    public function persistEloquentInsert(array $values)
    {
        $persistence = Orm::getEloquentPersistence();

        $persistence->persistEloquentQueryForInsert($this, $values);

        return $this;
    }

    /**
     * @return static
     */
    public function persistEloquentUpdate(array $values)
    {
        $persistence = Orm::getEloquentPersistence();

        $persistence->persistEloquentQueryForUpdate($this, $values);

        return $this;
    }

    /**
     * @return static
     */
    public function persistEloquentDelete()
    {
        $persistence = Orm::getEloquentPersistence();

        $persistence->persistEloquentQueryForDelete($this);

        return $this;
    }
}
