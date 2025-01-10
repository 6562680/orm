<?php

namespace Gzhegow\Database\Core\Relation\Factory;


use Gzhegow\Database\Core\Relation\Spec\HasOneSpec;
use Gzhegow\Database\Core\Relation\Spec\HasManySpec;
use Gzhegow\Database\Core\Relation\Spec\MorphToSpec;
use Gzhegow\Database\Core\Relation\Spec\MorphOneSpec;
use Gzhegow\Database\Core\Relation\Spec\BelongsToSpec;
use Gzhegow\Database\Core\Relation\Spec\MorphManySpec;
use Gzhegow\Database\Core\Relation\Spec\MorphToManySpec;
use Gzhegow\Database\Core\Relation\Spec\BelongsToManySpec;
use Gzhegow\Database\Core\Relation\Spec\HasOneThroughSpec;
use Gzhegow\Database\Core\Relation\Spec\HasManyThroughSpec;
use Gzhegow\Database\Package\Illuminate\Database\Eloquent\Relations\HasOne;
use Gzhegow\Database\Package\Illuminate\Database\Eloquent\Relations\HasMany;
use Gzhegow\Database\Package\Illuminate\Database\Eloquent\Relations\MorphTo;
use Gzhegow\Database\Package\Illuminate\Database\Eloquent\Relations\MorphOne;
use Gzhegow\Database\Package\Illuminate\Database\Eloquent\Relations\BelongsTo;
use Gzhegow\Database\Package\Illuminate\Database\Eloquent\Relations\MorphMany;
use Gzhegow\Database\Package\Illuminate\Database\Eloquent\Relations\MorphToMany;
use Gzhegow\Database\Package\Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Gzhegow\Database\Package\Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Gzhegow\Database\Package\Illuminate\Database\Eloquent\Relations\HasManyThrough;


/**
 * @mixin EloquentRelationFactory
 */
interface EloquentRelationFactoryInterface
{
    public function newBelongsTo(BelongsToSpec $spec) : BelongsTo;


    public function newHasOne(HasOneSpec $spec) : HasOne;

    public function newHasMany(HasManySpec $spec) : HasMany;


    public function newBelongsToMany(BelongsToManySpec $spec) : BelongsToMany;


    public function newHasOneThrough(HasOneThroughSpec $spec) : HasOneThrough;

    public function newHasManyThrough(HasManyThroughSpec $spec) : HasManyThrough;


    public function newMorphOne(MorphOneSpec $spec) : MorphOne;

    public function newMorphMany(MorphManySpec $spec) : MorphMany;


    public function newMorphTo(MorphToSpec $spec) : MorphTo;

    public function newMorphToMany(MorphToManySpec $spec) : MorphToMany;
}