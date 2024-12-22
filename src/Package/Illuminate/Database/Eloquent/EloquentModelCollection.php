<?php

namespace Gzhegow\Database\Package\Illuminate\Database\Eloquent;

use Gzhegow\Database\Exception\LogicException;
use Gzhegow\Database\Exception\RuntimeException;
use Illuminate\Database\Eloquent\Collection as EloquentModelCollectionBase;


/**
 * @template-covariant T of EloquentModel
 */
class EloquentModelCollection extends EloquentModelCollectionBase
{
    /**
     * @var class-string<T>
     */
    protected $modelClass;

    /**
     * @param T|class-string<T> $modelOrClass
     *
     * @return static
     */
    public function setModelClass($modelOrClass)
    {
        /** @var class-string<T> $modelClass */

        $modelClass = is_object($modelOrClass)
            ? get_class($modelOrClass)
            : $modelOrClass;

        if (! is_subclass_of($modelOrClass, EloquentModel::class)) {
            throw new LogicException(
                [
                    'The `modelOrClass` should be instance of class-string of: ' . EloquentModel::class,
                    $modelOrClass,
                ]
            );
        }

        $this->modelClass = $modelClass;

        return $this;
    }

    /**
     * @return class-string<T>
     */
    public function getModelClass() : string
    {
        return $this->modelClass;
    }


    public function load($relations)
    {
        if (! $this->items) {
            return $this;
        }

        $this->assertLoadAllowed(__FUNCTION__);

        return parent::load($relations);
    }

    public function loadMissing($relations)
    {
        if (! $this->items) {
            return $this;
        }

        $this->assertLoadAllowed(__FUNCTION__);

        return parent::loadMissing($relations);
    }

    public function loadCount($relations)
    {
        if (! $this->items) {
            return $this;
        }

        $this->assertLoadAllowed(__FUNCTION__);

        return parent::loadCount($relations);
    }

    public function loadMorph($relation, $relations)
    {
        if (! $this->items) {
            return $this;
        }

        $this->assertLoadAllowed(__FUNCTION__);

        return parent::loadMorph($relation, $relations);
    }

    public function loadMorphCount($relation, $relations)
    {
        if (! $this->items) {
            return $this;
        }

        $this->assertLoadAllowed(__FUNCTION__);

        return parent::loadMorphCount($relation, $relations);
    }


    protected function assertLoadAllowed(string $function = null) : void
    {
        $function = $function ?? __FUNCTION__;

        foreach ( $this->items as $item ) {
            if (! is_a($item, EloquentModel::class)) {
                throw new RuntimeException(
                    "Unable to call {$function}() due to collection contains non-models"
                );
            }

            if (! $item->exists) {
                throw new RuntimeException(
                    "Unable to call {$function}() due to collection contains models that is not exists in DB"
                );
            }
        }
    }
}
