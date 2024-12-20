<?php

namespace Gzhegow\Database\Core;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Database\Schema\Builder as EloquentSchemaBuilder;
use Gzhegow\Database\Package\Illuminate\Database\Eloquent\EloquentModel;
use Gzhegow\Database\Package\Illuminate\Database\EloquentPdoQueryBuilder;
use Gzhegow\Database\Package\Illuminate\Database\Schema\EloquentSchemaBlueprint;
use Gzhegow\Database\Package\Illuminate\Database\Eloquent\EloquentModelCollection;
use Gzhegow\Database\Package\Illuminate\Database\Eloquent\EloquentModelQueryBuilder;


interface OrmFactoryInterface
{
    public function newEloquentSchemaBuilder(
        ConnectionInterface $connection
    ) : EloquentSchemaBuilder;

    public function newEloquentSchemaBlueprint(
        $table,
        \Closure $callback = null,
        $prefix = ''
    ) : EloquentSchemaBlueprint;


    public function newEloquentPdoQueryBuilder(
        ConnectionInterface $connection,
        Grammar $grammar = null,
        Processor $processor = null
    ) : EloquentPdoQueryBuilder;

    /**
     * @template-covariant T of EloquentModel
     *
     * @param T $model
     *
     * @return EloquentModelQueryBuilder<T>
     */
    public function newEloquentModelQueryBuilder(
        EloquentPdoQueryBuilder $query,
        //
        EloquentModel $model
    ) : EloquentModelQueryBuilder;


    /**
     * @template-covariant T of EloquentModel
     *
     * @param iterable<T> $models
     *
     * @return EloquentModelCollection<T>
     */
    public function newEloquentModelCollection(
        iterable $models = []
    ) : EloquentModelCollection;
}