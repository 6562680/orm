<?php

namespace Gzhegow\Database\Package\Illuminate\Database\Eloquent\Relations;

use Gzhegow\Database\Core\Relation\Traits\HasRelationNameTrait;
use Illuminate\Database\Eloquent\Relations\HasOneThrough as BaseHasOneThrough;


class HasOneThrough extends BaseHasOneThrough implements
    RelationInterface
{
    use HasRelationNameTrait;


    public function addConstraints()
    {
        /** @see parent::addConstraints() */

        $localValue = $this->farParent->getAttribute($this->localKey);

        $this->performJoin();

        if (static::$constraints) {
            $this->query->where(
                $this->getQualifiedFirstKeyName(),
                '=',
                $localValue
            );
        }
    }
}