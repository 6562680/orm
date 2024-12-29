<?php

namespace Gzhegow\Database\Package\Illuminate\Database\Eloquent;

use Gzhegow\Lib\Lib;
use Gzhegow\Database\Exception\LogicException;
use Gzhegow\Database\Exception\RuntimeException;
use Gzhegow\Database\Core\Model\Traits\LoadTrait;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Gzhegow\Database\Core\Model\Traits\TableTrait;
use Gzhegow\Database\Core\Model\Traits\QueryTrait;
use Gzhegow\Database\Core\Model\Traits\ChunkTrait;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Gzhegow\Database\Core\Model\Traits\FactoryTrait;
use Gzhegow\Database\Core\Model\Traits\ColumnsTrait;
use Gzhegow\Database\Core\Model\Traits\CalendarTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Gzhegow\Database\Core\Model\Traits\AttributeTrait;
use Gzhegow\Database\Core\Model\Traits\PersistenceTrait;
use Illuminate\Database\Eloquent\Model as EloquentModelBase;
use Illuminate\Database\Eloquent\Relations\Concerns\AsPivot;
use Gzhegow\Database\Core\Model\Traits\Relation\RelationTrait;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Gzhegow\Database\Core\Model\Traits\Relation\RelationFactoryTrait;
use Gzhegow\Database\Exception\Exception\Resource\ResourceNotFoundException;


abstract class EloquentModel extends EloquentModelBase
{
    use RelationFactoryTrait;
    use RelationTrait;

    use AttributeTrait;
    use ChunkTrait;
    use ColumnsTrait;
    use FactoryTrait;
    use LoadTrait;
    use QueryTrait;
    use TableTrait;

    use CalendarTrait;
    use PersistenceTrait;


    // >>> metadata
    protected $table;
    protected $tablePrefix;
    protected $tableNoPrefix;
    protected $primaryKey = 'id';

    // >>> settings
    public $incrementing = true;
    public $timestamps   = false;

    // >>> strict mode
    /**
     * > позволяет отключить `casts` и динамические аттрибуты, то есть использовать только те, которые были получены из БД
     * > `false|null` -> вернет null; `true` -> бросит исключение
     */
    public $preventsLazyGet = true;
    /**
     * > позволяет гарантировать, что полученная из БД модель не будет изменяться напрямую с помощью __set()/__offsetSet()
     * > модель нужно будет создавать со свойством `recentlyCreated = true` или использовать ->fill() для проставления значений
     * > `false|null` -> действие будет пропущено; `true` -> бросит исключение
     */
    public $preventsLazySet = true;
    /**
     * > позволяет отключить ленивый запрос по связи, если связь не была запрошена явно через $query->with() или $model->load()
     * > `null` -> вернет null|default; `false` -> выполнит SQL SELECT; `true` -> бросит исключение
     */
    public $preventsLazyLoading = null;

    // >>> attributes
    protected $attributes = [];
    protected $casts      = [];
    protected $dates      = [];
    /**
     * > если в запрос не указать вручную ->select()/->addSelect() колонки, то будут выбраны указанные ниже
     * > используйте алиасы '*' для `SELECT *` и '#' для `SELECT {primaryKey}`
     * > запросы следует писать так, чтобы для PROD окружения было ['#'], но для DEV удобнее ['*']
     */
    protected $columns = [ '*' ];
    /** > автоматическое преобразование ключей в `snake_case` при вызове ->toArray() */
    public static $snakeAttributes = false;

    // >>> relations
    protected $relations = [];
    protected $touches   = [];

    // >>> serialization
    /** > список полей, которые принудительно скрываются при ->toArray() */
    protected $hidden       = [];
    protected $hiddenLoaded = false;
    /** > список полей, которые принудительно отображаются при ->toArray() */
    protected $visible       = [];
    protected $visibleLoaded = false;

    // >>> state
    /** > SELECT был сделан в рамках этого скрипта, чтобы создать сущность и наполнить её из БД */
    public $exists = false;
    /** > gzhegow, модель создана, чтобы выполнить INSERT в рамках текущего скрипта */
    public $recentlyCreated = false;
    /** > INSERT/UPDATE был сделан в рамках этого скрипта, т.е. модель создана "недавно", `exists` тоже будет true */
    public $wasRecentlyCreated = false;


    /**
     * @return static
     */
    public static function new(array $attributes = null, \Closure $fnInitialize = null)
    {
        $attributes = $attributes ?? [];

        $instance = new static($attributes);
        $instance->recentlyCreated = true;

        if (null !== $fnInitialize) {
            $fnInitialize->call($instance, $instance);
        }

        return $instance;
    }


    /**
     * @return static
     */
    public static function from($from, \Closure $fnInitialize = null) // : static
    {
        $instance = static::tryFrom($from, $fnInitialize, $error);

        if (null === $instance) {
            throw $error;
        }

        return $instance;
    }

    /**
     * @return static|null
     */
    public static function tryFrom($from, \Closure $fnInitialize = null, \Throwable &$last = null) // : ?static
    {
        $last = null;

        Lib::php()->errors_start($b);

        $instance = null
            ?? static::tryFromInstance($from, $fnInitialize)
            ?? static::tryFromArray($from, $fnInitialize)
            ?? static::tryFromStd($from, $fnInitialize);

        $errors = Lib::php()->errors_end($b);

        if (null === $instance) {
            foreach ( $errors as $error ) {
                $last = new LogicException($error, null, $last);
            }
        }

        return $instance;
    }


    /**
     * @return static|null
     */
    public static function tryFromInstance($from, \Closure $fnInitialize = null) // : ?static
    {
        if (! is_a($from, static::class)) {
            return Lib::php()->error(
                [ 'The `from` should be instance of: ' . static::class, $from ]
            );
        }

        $model = static::getModel();

        $rawAttributes = $from->getRawAttributes();

        foreach ( $rawAttributes as $key => $value ) {
            if (! $model->isFillable($key)) {
                unset($rawAttributes[ $key ]);
            }
        }

        $instance = $from::new($rawAttributes, $fnInitialize);

        return $instance;
    }

    /**
     * @return static|null
     */
    public static function tryFromArray($from, \Closure $fnInitialize = null) // : ?static
    {
        if (! is_array($from)) {
            return Lib::php()->error([ 'The `from` should be array', $from ]);
        }

        $instance = static::new($from, $fnInitialize);

        return $instance;
    }

    /**
     * @return static|null
     */
    public static function tryFromStd($from, \Closure $fnInitialize = null) : ?self
    {
        if (! is_a($from, \stdClass::class)) {
            return Lib::php()->error([ 'The `from` should be \stdClass', $from ]);
        }

        $instance = static::new((array) $from, $fnInitialize);

        return $instance;
    }


    public function __isset($key)
    {
        return $this->offsetExists($key);
    }

    public function __get($key)
    {
        return $this->offsetGet($key);
    }

    public function __set($key, $value)
    {
        return $this->offsetSet($key, $value);
    }

    public function __unset($key)
    {
        return $this->offsetUnset($key);
    }


    public function offsetExists($offset)
    {
        if ($this->isRelationAttribute($offset)) {
            $exists = $this->isRelationAttributeExists($offset);

            return $exists;
        }

        if ($this->isModelAttribute($offset)) {
            $exists = $this->isModelAttributeValueOrGetterExists($offset);

            return $exists;
        }

        return false;
    }

    public function offsetGet($offset)
    {
        if ($this->isRelationAttribute($offset)) {
            $value = $this->getRelationValue($offset);

            return $value;
        }

        if ($this->isModelAttribute($offset)) {
            if ($this->preventsLazyGet) {
                if ($this->exists) {
                    $existsAttribute = $this->isModelAttributeValueExists($offset);

                    if (! $existsAttribute) {
                        $existsGetter = $this->isModelAttributeGetterExists($offset);

                        if (! $existsGetter) {
                            throw new RuntimeException(
                                'Attribute is missing: `' . $offset . '`.'
                                . ' This message is shown because `preventsLazyGet` is set to TRUE'
                            );
                        }
                    }
                }
            }

            $value = $this->getModelAttribute($offset);

            return $value;
        }

        return null;
    }

    public function offsetSet($offset, $value)
    {
        if ($this->isRelationAttribute($offset)) {
            $this->setRelationAttribute($offset, $value);
        }

        if ($this->isModelAttribute($offset)) {
            if ($this->preventsLazySet) {
                if (! $this->recentlyCreated) {
                    throw new RuntimeException(
                        'Unable to set attribute due to model `preventsLazySet` is enabled: ' . $offset
                    );
                }
            }

            if ($this->getKeyName() === $offset) {
                throw new RuntimeException(
                    [
                        'Primary key should be allocated using ->setupUuid() or auto(-increment) by remote storage: ' . $offset,
                        $offset,
                        $value,
                    ]
                );
            }

            $this->setModelAttribute($offset, $value);
        }

        return $this;
    }

    public function offsetUnset($offset)
    {
        if ($this->isRelationAttribute($offset)) {
            unset($this->relations[ $offset ]);
        }

        if ($this->isModelAttribute($offset)) {
            unset($this->attributes[ $offset ]);
        }

        return $this;
    }


    public function getKey()
    {
        /** @see parent::getKey(); */

        $key = $this->getKeyName();
        $value = $this->getAttribute($key);

        return $value;
    }

    public function getKeyName()
    {
        /** @see parent::getKeyName() */

        return $this->primaryKey;
    }


    /**
     * @return string
     *
     * @deprecated
     * @internal
     */
    public function getForeignKey()
    {
        /** @see parent::getForeignKey(); */

        $table = $this->getTable();
        $key = $this->getKeyName();

        return "{$table}_{$key}";
    }

    /**
     * @noinspection PhpDeprecationInspection
     */
    public function getForeignKeyName()
    {
        $key = $this->getForeignKey();

        return $key;
    }


    /**
     * @return static
     */
    public function fill(array $attributes)
    {
        /** @see parent::fill() */

        foreach ( $attributes as $attr => $value ) {
            if (! $this->isFillable($attr)) {
                throw new RuntimeException(
                    [
                        'Attribute is not fillable: ' . $attr,
                        $this,
                    ]
                );
            }

            $this->attributes[ $attr ] = $value;
        }

        return $this;
    }

    /**
     * @return static
     */
    public function fillPassed(array $attributes)
    {
        $_attributes = Lib::bool()->passed($attributes);

        $this->fill($_attributes);

        return $this;
    }


    public function save(array $options = null) : bool
    {
        $options = $options ?? [];

        $relationForeignKeys = [];
        foreach ( $this->relations as $relationName => $relationValue ) {
            if (! (false
                || $this->hasRelationOfClass($relationName, BelongsTo::class)
                || $this->hasRelationOfClass($relationName, MorphTo::class)
            )) {
                continue;
            }

            /** @var BelongsTo $relation */
            $relation = $this->{$relationName}();

            if (null === $relationValue) {
                $relation->dissociate();

            } else {
                $relation->associate($relationValue);

                $relationForeignKey = $relation->getForeignKeyName();

                $relationForeignKeys[ $relationForeignKey ] = $relationName;
            }
        }

        foreach ( $this->attributes as $key => $value ) {
            if (isset($relationForeignKeys[ $key ]) && is_object($value)) {
                throw new RuntimeException(
                    'Unable to associate foreign key: '
                    . $relationForeignKeys[ $key ] . ' / ' . $key
                );
            }
        }

        $status = parent::save($options);

        return $status;
    }

    public function delete()
    {
        try {
            $status = parent::delete();
        }
        catch ( \Exception $e ) {
            throw new RuntimeException($e);
        }

        return $status;
    }


    public function saveRecursive() : bool
    {
        $graph = [];

        if (null === $this->doSaveRecursive($graph)) {
            return false;
        }

        return true;
    }

    private function doSaveRecursive(array &$graph = null) : ?array
    {
        /** @var static $child */

        $graph = $graph ?? [];

        $splHash = spl_object_hash($this);

        if (isset($graph[ $splHash ])) {
            return $graph;
        }

        $graph[ $splHash ] = $this;

        // > gzhegow, this will remove cross-links for garbage collector
        $relationsToUnset = [];

        foreach ( $this->relations as $relationName => $relationValue ) {
            if (null === $relationValue) {
                continue;
            }

            if (! (false
                || $this->hasRelationOfClass($relationName, BelongsTo::class)
                || $this->hasRelationOfClass($relationName, MorphTo::class)
            )) {
                continue;
            }

            $relationsToUnset[ $relationName ] = true;

            /** @var EloquentModel $parent */
            $parent = $relationValue;

            // ! recursion
            if (null === $parent->doSaveRecursive($graph)) {
                return null;
            }
        }

        $status = $this->save();
        if (! $status) {
            return null;
        }

        foreach ( $this->relations as $relationName => $relationValue ) {
            if (null === $relationValue) {
                continue;
            }

            if (isset($relationsToUnset[ $relationName ])) {
                continue;
            }

            $children = is_a($relationValue, EloquentCollection::class)
                ? $relationValue->all()
                : ($relationValue ? [ $relationValue ] : []);

            foreach ( $children as $child ) {
                /** @var EloquentModel $model */

                // ! recursion
                if (null === $child->doSaveRecursive($graph)) {
                    return null;
                }
            }
        }

        // > gzhegow, remove cross-links for garbage collector
        foreach ( $relationsToUnset as $relationName => $bool ) {
            unset($this->relations[ $relationName ]);
        }

        return $graph;
    }


    public function deleteRecursive() : ?array
    {
        $graph = [];

        $graph = $this->doDeleteRecursive($graph);

        return $graph;
    }

    private function doDeleteRecursive(array &$graph = null) : ?array
    {
        /** @var static $model */

        $graph = $graph ?? [];

        $splHash = spl_object_hash($this);

        if (isset($graph[ $splHash ])) {
            return $graph;
        }

        $graph[ $splHash ] = $this->relations;

        foreach ( $this->relations as $relationName => $relationValue ) {
            if (null === $relationValue) {
                continue;
            }

            if (false
                || $this->hasRelationOfClass($relationName, BelongsTo::class)
                || $this->hasRelationOfClass($relationName, MorphTo::class)
            ) {
                continue;
            }

            $models = is_a($relationValue, EloquentCollection::class)
                ? $relationValue->all()
                : ($relationValue ? [ $relationValue ] : []);

            foreach ( $models as $model ) {
                if (null === $model->doDeleteRecursive($graph)) {
                    return null;
                }
            }
        }

        if (! $this->delete()) {
            return null;
        }

        return $graph;
    }


    /**
     * @return EloquentModelCollection<static>|static[]
     */
    public static function get(EloquentModelQueryBuilder $query, $columnsDefault = null)
    {
        return $query->get($columnsDefault);
    }

    /**
     * @return static|null
     */
    public static function first(EloquentModelQueryBuilder $query, $columnsDefault = null)
    {
        return $query->first($columnsDefault);
    }

    /**
     * @return static
     * @throws ResourceNotFoundException
     */
    public static function firstOrFail(EloquentModelQueryBuilder $query, $columnsDefault = null)
    {
        return $query->firstOrFail($columnsDefault);
    }


    /**
     * > gzhegow, немного измененный вывод объекта в json, чтобы свойства со связями не перемешивались
     */
    public function jsonSerialize()
    {
        /** @see parent::jsonSerialize() */

        $array = $this->attributesToArray();

        if ($relationsArray = $this->relationsToArray()) {
            ksort($relationsArray);

            $array[ '_relations' ] = $relationsArray;
        }

        return $array;
    }


    /**
     * @return static|null
     */
    public function fresh($with = [])
    {
        /** @see parent::fresh() */

        if (! $this->exists) {
            return null;
        }

        $_with = is_string($with)
            ? func_get_args()
            : $with;

        $query = $this->newModelQuery();

        $query->with($this->with);
        $query->with($_with);
        $query->withCount($this->withCount);

        $this->setKeysForSelectQuery($query);

        $model = $query->first([ '*' ]);

        return $model;
    }

    /**
     * @return static
     *
     * @throws ResourceNotFoundException
     */
    public function refresh()
    {
        /** @see parent::refresh() */

        if (! $this->exists) {
            return $this;
        }

        $query = $this->newModelQuery();

        $query->with($this->with);
        $query->withCount($this->withCount);

        $this->setKeysForSelectQuery($query);

        $model = $query->firstOrFail([ '*' ]);

        $this->setRawAttributes($model->getRawAttributes());

        $with = [];
        foreach ( $this->relations as $i => $relation ) {
            if ($relation instanceof Pivot) {
                continue;
            }

            if (is_object($relation)) {
                $classUses = Lib::php()->class_uses_with_parents(
                    $relation,
                    true
                );

                if (in_array(AsPivot::class, $classUses, true)) {
                    continue;
                }
            }

            $with[ $i ] = true;
        }

        $this->load(array_keys($with));

        $this->syncOriginal();

        return $this;
    }


    /**
     * @return static
     */
    public static function getModel()
    {
        return static::$models[ $class = static::class ] = null
            ?? static::$models[ $class ]
            ?? new static();
    }

    /**
     * @var array
     */
    protected static $models = [];
}
