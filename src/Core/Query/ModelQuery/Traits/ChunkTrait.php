<?php

namespace Gzhegow\Database\Core\Query\ModelQuery\Traits;

use Gzhegow\Database\Core\Query\Chunks\ChunksBuilder;
use Gzhegow\Database\Package\Illuminate\Database\Eloquent\EloquentModel;
use Gzhegow\Database\Package\Illuminate\Database\Eloquent\EloquentModelCollection;
use Gzhegow\Database\Package\Illuminate\Database\Eloquent\EloquentModelQueryBuilder;


/**
 * @template-covariant T of EloquentModel
 *
 * @mixin EloquentModelQueryBuilder
 */
trait ChunkTrait
{
    /**
     * @return ChunksBuilder
     */
    public function chunks() : ChunksBuilder
    {
        $builder = ChunksBuilder::from($this);

        return $builder;
    }


    /**
     * @return \Generator<EloquentModelCollection<T>>
     */
    public function chunksModelNativeForeach(
        int $limitChunk, int $limit = null,
        int $offset = null
    ) : \Generator
    {
        $builder = ChunksBuilder::from($this);

        $builder
            ->chunksModelNativeForeach(
                $limitChunk, $limit,
                $offset
            )
        ;

        $generator = $builder->chunksForeach();

        return $generator;
    }

    /**
     * @return \Generator<EloquentModelCollection<T>>
     */
    public function chunksModelAfterForeach(
        int $limitChunk, int $limit = null,
        string $offsetColumn = null, string $offsetOperator = null, $offsetValue = null, bool $includeOffsetValue = null
    ) : \Generator
    {
        $builder = ChunksBuilder::from($this);

        $builder
            ->chunksModelAfterForeach(
                $limitChunk, $limit,
                $offsetColumn, $offsetOperator, $offsetValue, $includeOffsetValue
            )
        ;

        $generator = $builder->chunksForeach();

        return $generator;
    }


    /**
     * @return \Generator<EloquentModelCollection<T>>
     */
    public function chunksPdoNativeForeach(
        int $limitChunk, int $limit = null,
        int $offset = null
    ) : \Generator
    {
        $builder = ChunksBuilder::from($this);

        $builder
            ->chunksPdoNativeForeach(
                $limitChunk, $limit,
                $offset
            )
        ;

        $generator = $builder->chunksForeach();

        return $generator;
    }

    /**
     * @return \Generator<EloquentModelCollection<T>>
     */
    public function chunksPdoAfterForeach(
        int $limitChunk, int $limit = null,
        string $offsetColumn = null, string $offsetOperator = null, $offsetValue = null, bool $includeOffsetValue = null
    ) : \Generator
    {
        $builder = ChunksBuilder::from($this);

        $builder
            ->chunksPdoAfterForeach(
                $limitChunk, $limit,
                $offsetColumn, $offsetOperator, $offsetValue, $includeOffsetValue
            )
        ;

        $generator = $builder->chunksForeach();

        return $generator;
    }


    public function paginateModelNativeForeach(
        int $perPage = null, int $page = null, int $pagesDelta = null,
        int $offset = null
    ) : ChunksBuilder
    {
        $builder = ChunksBuilder::from($this);

        $builder
            ->paginateModelNativeForeach(
                $perPage, $page, $pagesDelta,
                $offset
            )
        ;

        return $builder;
    }

    public function paginateModelAfterForeach(
        int $perPage = null, int $page = null, int $pagesDelta = null,
        string $offsetColumn = null, string $offsetOperator = null, $offsetValue = null, bool $includeOffsetValue = null
    ) : ChunksBuilder
    {
        $builder = ChunksBuilder::from($this);

        $builder
            ->paginateModelAfterForeach(
                $perPage, $page, $pagesDelta,
                $offsetColumn, $offsetOperator, $offsetValue, $includeOffsetValue
            )
        ;

        return $builder;
    }


    public function paginatePdoNativeForeach(
        int $perPage = null, int $page = null, int $pagesDelta = null,
        int $offset = null
    ) : ChunksBuilder
    {
        $builder = ChunksBuilder::from($this);

        $builder
            ->paginatePdoNativeForeach(
                $perPage, $page, $pagesDelta,
                $offset
            )
        ;

        return $builder;
    }

    public function paginatePdoAfterForeach(
        int $perPage = null, int $page = null, int $pagesDelta = null,
        string $offsetColumn = null, string $offsetOperator = null, $offsetValue = null, bool $includeOffsetValue = null
    ) : ChunksBuilder
    {
        $builder = ChunksBuilder::from($this);

        $builder
            ->paginatePdoAfterForeach(
                $perPage, $page, $pagesDelta,
                $offsetColumn, $offsetOperator, $offsetValue, $includeOffsetValue
            )
        ;

        return $builder;
    }
}
