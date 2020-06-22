<?php

namespace hs\ClosureTable\Traits;

use hs\ClosureTable\Exceptions\ClosureTableException;
use think\helper\Arr;
use think\Model;
use hs\ClosureTable\Extensions\Str;
use hs\ClosureTable\Extensions\CollectionExtension;
use Throwable;

trait ClosureTable
{
    /**
     * @title  检查给定数据是否已经修改过了
     * @desc   方法描述
     * @param $changes
     * @param null $attributes
     * @return bool
     * @author HuangSen
     * DateTime: 2020/5/14 17:56
     */
    public function hasChanges($changes, $attributes = null): bool
    {
        if (empty($attributes)) {
            return count($changes) > 0;
        }

        foreach (Arr::wrap($attributes) as $attribute) {
            if (array_key_exists($attribute, $changes)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @title  更新前
     * @desc   方法描述
     * @param Model $model
     * @author HuangSen
     * DateTime: 2020/5/20 9:55
     */
    public static function onBeforeUpdate(Model $model): void
    {
//        echo '更新前！<br/>';
        if ($model->hasChanges($model->getChangedData(), $model->getParentColumn())) {
            $model->updateClosure();
        }
    }

    /**
     * @title  新增后事件
     * @desc   方法描述
     * @param Model $model
     * @author HuangSen
     * DateTime: 2020/5/20 9:57
     */
    public static function onAfterInsert(Model $model): void
    {
//        echo '新增后！<br/>';
        $model->insertClosure($model->getParentKey() ?: 0);
    }

    /**
     * @title  删除前
     * @desc   方法描述
     * @param Model $model
     * @author HuangSen
     * DateTime: 2020/5/20 9:58
     */
    public static function onBeforeDelete(Model $model): void
    {
//        echo '删除前！<br/>';
        $model->deleteObservers();
    }

    /**
     * Eloquent Listener
     */
    public static function boot(): void
    {

        if (method_exists(new static, 'restored')) {
            static::restored(static function (Model $model) {
                $model->insertClosure($model->getParentKey() ?: 0);
            });
        }
    }

    /**
     * 获取此关系闭包表
     *
     * @return string
     */
    public function getClosureTable(): string
    {
        if (!isset($this->closureTable)) {
            return str_replace('\\', '', Str::snake(class_basename($this))) . '_closure';
        }
        return $this->closureTable;
    }

    public function getPrefixedClosureTable(): string
    {
//        return $this->db()->getConnection()->getConfig('prefix') . $this->getClosureTable();
        return $this->getClosureTable();
    }

    public function getPrefixedTable()
    {
        return $this->getTable();
    }

    /**
     * 获取此闭包表祖先列
     *
     * @return string
     */
    public function getAncestorColumn(): string
    {
        if (!isset($this->ancestorColumn)) {
            return 'ancestor';
        }
        return $this->ancestorColumn;
    }

    /**
     * 获取此闭包表后代列
     *
     * @return string
     */
    public function getDescendantColumn(): string
    {
        if (!isset($this->descendantColumn)) {
            return 'descendant';
        }
        return $this->descendantColumn;
    }

    /**
     * 获取此闭包表距离（层次结构）列
     *
     * @return string
     */
    public function getDistanceColumn(): string
    {
        if (!isset($this->distanceColumn)) {
            return 'distance';
        }
        return $this->distanceColumn;
    }

    /**
     * 获取父级字段
     *
     * @return string
     */
    public function getParentColumn(): string
    {
        if (!isset($this->parentColumn)) {
            return 'pid';
        }
        return $this->parentColumn;
    }

    /**
     * 获取具有表名的祖先字段
     *
     * @return string
     */
    public function getQualifiedAncestorColumn(): string
    {
        return $this->getClosureTable() . '.' . $this->getAncestorColumn();
    }

    /**
     * 获取具有表名的后代字段
     *
     * @return string
     */
    protected function getQualifiedDescendantColumn(): string
    {
        return $this->getClosureTable() . '.' . $this->getDescendantColumn();
    }

    /**
     * 获取具有表名的距离字段
     *
     * @return string
     */
    protected function getQualifiedDistanceColumn(): string
    {
        return $this->getClosureTable() . '.' . $this->getDistanceColumn();
    }

    /**
     * 获取具有表名的父级字段
     *
     * @return string
     */
    protected function getQualifiedParentColumn(): string
    {
        return $this->getTable() . '.' . $this->getParentColumn();
    }

    /**
     * @return mixed
     */
    protected function getParentKey()
    {
        return $this->getAttr($this->getParentColumn());
    }

    /**
     * @param $key
     */
    protected function setParentKey($key): void
    {
        $this->settAttr($this->getParentColumn(), $key);
    }

    /**
     * 根据模型的表限定给定的列名.
     *
     * @param string $column
     * @return string
     */
    public function qualifyColumn($column): string
    {
        if (Str::contains($column, '.')) {
            return $column;
        }

        return $this->getTable() . '.' . $column;
    }

    /**
     * 连接闭包表
     *
     * @param $column
     * @param bool $withSelf
     * @return mixed
     */
    protected function joinRelationBy($column, $withSelf = false)
    {
        //判断数据是否存在
//        if (!$this->exists) {
//            throw new ModelNotFoundException();
//        }

        $query = null;
        $keyName = $this->qualifyColumn($this->getPk());
        $key = $this->getAttr($this->getPk());
        $closureTable = $this->getClosureTable();
        $ancestor = $this->getQualifiedAncestorColumn();
        $descendant = $this->getQualifiedDescendantColumn();
        $distance = $this->getQualifiedDistanceColumn();

        switch ($column) {
            case 'ancestor':
                $query = $this->join($closureTable, $ancestor . '=' . $keyName)
                    ->where($descendant, '=', $key);
                break;

            case 'descendant':
                $query = $this->join($closureTable, $descendant . '=' . $keyName)
                    ->where($ancestor, '=', $key);
                break;
        }

        $operator = ($withSelf === true ? '>=' : '>');

        $query->where($distance, $operator, 0);

        return $query;
    }

    /**
     * 获得自我关系
     *
     * @return mixed
     */
    protected function joinRelationSelf()
    {
//        if (!$this->exists) {
//            throw new ModelNotFoundException();
//        }

        $keyName = $this->qualifyColumn($this->getPk());
        $key = $this->getAttr($this->getPk());
        $closureTable = $this->getClosureTable();
        $ancestor = $this->getQualifiedAncestorColumn();
        $descendant = $this->getQualifiedDescendantColumn();
        $distance = $this->getQualifiedDistanceColumn();
        return $this
            ->join($closureTable, $keyName . '=' . $ancestor)
            ->where($ancestor, $key)
            ->where($descendant, $key)
            ->where($distance, 0);
    }

    /**
     * 无闭合表获取
     *
     * @return mixed
     */
    protected function joinWithoutClosure()
    {
        $keyName = $this->qualifyColumn($this->getPk());

        $closureTable = $this->getClosureTable();
        $ancestor = $this->getQualifiedAncestorColumn();
        $descendant = $this->getQualifiedDescendantColumn();
        return $this
            ->leftJoin($closureTable, $keyName . '=' . $ancestor)
            ->whereNull($ancestor)
            ->whereNull($descendant);
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeIsolated($query)
    {
        $keyName = $this->qualifyColumn($this->getPk());
        $closureTable = $this->getClosureTable();
        $ancestor = $this->getQualifiedAncestorColumn();
        $descendant = $this->getQualifiedDescendantColumn();
        return $query
            ->leftJoin($closureTable, $keyName . '=' . $ancestor)
            ->whereNull($ancestor)
            ->whereNull($descendant);
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeOnlyRoot($query)
    {
        $parentColumn = $this->getParentColumn();
        return $query->where($parentColumn, 0)
            ->whereOr(static function ($query) use ($parentColumn) {
                $query->whereNull($parentColumn);
            });
    }

    /**
     * 插入与闭包表的节点关系
     *
     * @param int $ancestorId
     * @return void
     */
    protected function insertClosure($ancestorId = 0): void
    {
//        halt($this->exists);
//        if (!$this->exists) {
//            throw new ModelNotFoundException();
//        }

        $descendantId = $this->getAttr($this->getPk());
        $prefixedTable = $this->getPrefixedClosureTable();
        $ancestorColumn = $this->getAncestorColumn();
        $descendantColumn = $this->getDescendantColumn();
        $distanceColumn = $this->getDistanceColumn();

        $sql = "
            INSERT INTO {$prefixedTable} ({$ancestorColumn}, {$descendantColumn}, {$distanceColumn})
            SELECT tbl.{$ancestorColumn}, {$descendantId}, tbl.{$distanceColumn}+1
            FROM {$prefixedTable} AS tbl
            WHERE tbl.{$descendantColumn} = {$ancestorId}
            UNION
            SELECT {$descendantId}, {$descendantId}, 0
        ";

        $this->db()->execute($sql);
    }

    /**
     * @param null $with
     * @return bool
     * @throws \think\db\exception\DbException
     */
    protected function detachSelfRelation($with = null): bool
    {
        $key = $this->getKey();
        $table = $this->getClosureTable();
        $ancestorColumn = $this->getAncestorColumn();
        $descendantColumn = $this->getDescendantColumn();
        switch ($with) {
            case 'ancestor':
                \think\facade\Db::table($table)->where($descendantColumn, $key)->delete();
                break;
            case 'descendant':
                \think\facade\Db::table($table)->where($ancestorColumn, $key)->delete();
                break;
            default:
                \think\facade\Db::table($table)->where($descendantColumn, $key)->whereOr($ancestorColumn, $key)->delete();
        }
        return true;
    }

    protected function deleteObservers(): void
    {
        if (!$this->exists) {
            throw new ModelNotFoundException();
        }
        $children = $this->getChildren();
        foreach ($children as $child) {
            $child->setParentKey(0);
            $child->save();
        }
        $this->detachRelationships();
        $this->detachSelfRelation();
    }

    /**
     * 把自己与祖先和后代与祖先的关系分开
     *
     * @return bool
     */
    protected function detachRelationships(): bool
    {
        if (!$this->exists) {
            throw new ModelNotFoundException();
        }

        $key = $this->getAttr($this->getPk());
        $prefixedTable = $this->getPrefixedClosureTable();
        $ancestorColumn = $this->getAncestorColumn();
        $descendantColumn = $this->getDescendantColumn();

        $sql = "
            DELETE FROM {$prefixedTable}
            WHERE {$descendantColumn} IN (
              SELECT d FROM (
                SELECT {$descendantColumn} as d FROM {$prefixedTable}
                WHERE {$ancestorColumn} = {$key}
              ) as dct
            )
            AND {$ancestorColumn} IN (
              SELECT a FROM (
                SELECT {$ancestorColumn} AS a FROM {$prefixedTable}
                WHERE {$descendantColumn} = {$key}
                AND {$ancestorColumn} <> {$key}
              ) as ct
            )
        ";

        $this->db()->execute($sql);
        return true;
    }

    /**
     * 把自己与祖先联系起来，把后代与祖先联系起来
     *
     * @param int|null $parentKey
     * @return bool
     */
    protected function attachTreeTo($parentKey = 0): bool
    {
        if (!$this->exists) {
            throw new ModelNotFoundException();
        }

        if (is_null($parentKey)) {
            $parentKey = 0;
        }

        $key = $this->getAttr($this->getPk());
        $prefixedTable = $this->getPrefixedClosureTable();
        $ancestorColumn = $this->getAncestorColumn();
        $descendantColumn = $this->getDescendantColumn();
        $distanceColumn = $this->getDistanceColumn();

        $sql = "
            INSERT INTO {$prefixedTable} ({$ancestorColumn}, {$descendantColumn}, {$distanceColumn})
            SELECT supertbl.{$ancestorColumn}, subtbl.{$descendantColumn}, supertbl.{$distanceColumn}+subtbl.{$distanceColumn}+1
            FROM (SELECT * FROM {$prefixedTable} WHERE {$descendantColumn} = {$parentKey}) as supertbl
            JOIN {$prefixedTable} as subtbl ON subtbl.{$ancestorColumn} = {$key}
        ";

        $this->db()->execute($sql);
        return true;
    }

    /**
     * 解除自我和后代所有关系的束缚
     *
     * @return bool
     */
    protected function deleteRelationships(): bool
    {
        if (!$this->exists) {
            throw new ModelNotFoundException();
        }

        $key = $this->getAttr($this->getPk());
        $prefixedTable = $this->getPrefixedClosureTable();
        $ancestorColumn = $this->getAncestorColumn();
        $descendantColumn = $this->getDescendantColumn();
        $sql = "
            DELETE FROM {$prefixedTable}
            WHERE {$descendantColumn} IN (
            SELECT d FROM (
              SELECT {$descendantColumn} as d FROM {$prefixedTable}
                WHERE {$ancestorColumn} = {$key}
              ) as dct
            )
        ";

        $this->db()->execute($sql);
        return true;
    }

    /**
     * 转换参数
     *
     * @param $parameter
     * @return Model|null
     */
    protected function parameter2Model($parameter): ?Model
    {
        $model = null;
        if ($parameter instanceof Model) {
            $model = $parameter;
        } elseif (is_numeric($parameter)) {
            $model = $this->findOrFail($parameter);
        } else {
            throw (new ModelNotFoundException)->setModel();
        }
        return $model;
    }

    /**
     * @throws ClosureTableException
     * @throws Throwable
     */
    protected function updateClosure(): void
    {
        if ($this->getParentKey()) {
            $parent = $this->parameter2Model($this->getParentKey());
            $parentKey = $parent->getAttr($parent->getPk());
        } else {
            $parentKey = 0;
        }

        $ids = $this->getDescendantsAndSelf([$this->getPk()])->pluck($this->getPk())->toArray();

        if (in_array($parentKey, $ids, true)) {
            throw new ClosureTableException('无法移动到后代节点');
        }
        $this->db()->transaction(function () use ($parentKey) {
            if (!$this->detachRelationships()) {
                throw new ClosureTableException('解除绑定关系失败');
            }
            if (!$this->attachTreeTo($parentKey)) {
                throw new ClosureTableException('关联树失败');
            }
        });
    }

    /**
     * 删除不存在的关系
     *
     * @return bool
     */
    public static function deleteRedundancies(): bool
    {
        $instance = new static;

        $segment = '';
        if (method_exists($instance, 'bootSoftDeletes')) {
            $segment = 'OR t.' . $instance->getDeletedAtColumn() . ' IS NOT NULL';
        }
        $table = $instance->getPrefixedTable();
        $prefixedTable = $instance->getPrefixedClosureTable();
        $ancestorColumn = $instance->getAncestorColumn();
        $descendantColumn = $instance->getDescendantColumn();
//        $keyName = $instance->getKeyName();
        $keyName = $instance->getPk();
        $sql = "
            DELETE ct FROM {$prefixedTable} ct
            WHERE {$descendantColumn} IN (
              SELECT d FROM (
                SELECT {$descendantColumn} as d FROM {$prefixedTable}
                LEFT JOIN {$table} t 
                ON {$descendantColumn} = t.{$keyName}
                WHERE t.{$keyName} IS NULL {$segment}
              ) as dct
            )
            OR {$ancestorColumn} IN (
              SELECT d FROM (
                SELECT {$ancestorColumn} as d FROM {$prefixedTable}
                LEFT JOIN {$table} t 
                ON {$ancestorColumn} = t.{$keyName}
                WHERE t.{$keyName} IS NULL {$segment}
              ) as act
            )
        ";
        \think\facade\Db::execute($sql);
        return true;
    }

    /**
     * 从数组创建子级
     *
     * @param array $attributes
     * @return mixed
     * @throws ClosureTableException
     */
    public function createChild(array $attributes)
    {
        if ($this->joinRelationSelf()->count() === 0) {
            throw new ClosureTableException('Model is not a node');
        }

        $parentKey = $this->getAttr($this->getPk());
        $attributes[$this->getParentColumn()] = $parentKey;
        return self::create($attributes);
    }

    /**
     * 将此模型设为根
     *
     * @return bool
     */
    public function makeRoot(): bool
    {
        if ($this->isRoot()) {
            return true;
        }
        $this->setParentKey(0);
        $this->save();
        return true;
    }

    /**
     * 将一个子项或多个子项与此模型关联，接受模型、字符串和数组
     *
     * @param $children
     * @return bool
     * @throws ClosureTableException
     * @throws Throwable
     */
    public function addChild($children): bool
    {
        if ($this->joinRelationSelf()->count() === 0) {
            throw new ClosureTableException('Model is not a node');
        }

        $keyName = $this->getPk();
        $key = $this->getKey();
        $ids = $this->getAncestorsAndSelf([$keyName])->pluck($keyName)->toArray();
        if (!(is_array($children) || $children instanceof Collection)) {
            $children = array($children);
        }
        \think\facade\Db::transaction(function () use ($children, $key, $ids) {
            foreach ($children as $child) {
                $model = $this->parameter2Model($child);
                if (in_array($model->getKey(), $ids, true)) {
                    throw new ClosureTableException('Children can\'t be ancestor');
                }
                $model->setParentKey($key);
                $model->save();
            }
        });

        return true;
    }

    /**
     * @param array $attributes
     * @return mixed
     * @throws ClosureTableException
     */
    public function createSibling(array $attributes)
    {
        if ($this->joinRelationSelf()->count() === 0) {
            throw new ClosureTableException('Model is not a node');
        }

        $parentKey = $this->getParent()->getKey();
        $attributes[$this->getParentColumn()] = $parentKey;
        return self::create($attributes);
    }

    /**
     * @param $siblings
     * @return bool
     */
    public function addSiblings($siblings): bool
    {
        $parent = $this->getParent();
        if (!$parent) {
            return false;
        }
        return $parent->addChild($siblings);
    }

    /**
     * @param $parent
     * @return bool
     */
    public function moveTo($parent): bool
    {
        $model = $this->parameter2Model($parent);
        $this->setParentKey($model->getKey());
        $this->save();

        return true;
    }

    /**
     * 修正模型与祖先的关系。如果你需要全部修复，请循环所有
     *
     * @return bool
     * @throws Throwable
     */
    public function perfectNode(): bool
    {
        $parentKey = $this->getParentKey() ?: 0;
        \think\facade\Db::transaction(function () use ($parentKey) {
            $this->detachSelfRelation('ancestor');
            $this->insertClosure($parentKey);
        });
        return true;
    }

    /**
     * 每一棵树的每一个项目，如果你的树太大小心它
     *
     * @return bool
     */
    public function perfectTree(): bool
    {
        $result = true;
        $this->getDescendants()->each(static function ($item) use (&$result) {
            if (!$item->perfectNode()) {
                $result = false;
                return false;
            }
        });
        return $result;
    }

    /**
     * 获取祖先查询
     *
     * @return mixed
     */
    public function queryAncestors()
    {
        return $this->joinRelationBy('ancestor');
    }

    /**
     * @param array $columns
     * @return mixed
     */
    public function getAncestors(array $columns = ['*'])
    {
        return $this->queryAncestors()->field($columns)->select();
    }

    /**
     * @return mixed
     */
    public function queryAncestorsAndSelf()
    {
        return $this->joinRelationBy('ancestor', true);
    }

    /**
     * @param array $columns
     * @return mixed
     */
    public function getAncestorsAndSelf(array $columns = ['*'])
    {
        return $this->queryAncestorsAndSelf()->field($columns)->select();
    }

    /**
     * @return mixed
     */
    public function queryDescendants()
    {
        return $this->joinRelationBy('descendant');
    }

    /**
     * @param array $columns
     * @return mixed
     */
    public function getDescendants(array $columns = ['*'])
    {
        return $this->queryDescendants()->field($columns)->select();
    }

    /**
     * @return mixed
     */
    public function queryDescendantsAndSelf()
    {
        return $this->joinRelationBy('descendant', true);
    }

    /**
     * @param array $columns
     * @return mixed
     */
    public function getDescendantsAndSelf(array $columns = ['*'])
    {
        return $this->queryDescendantsAndSelf()->field($columns)->select();
    }

    /**
     * @return mixed
     */
    public function queryBesides()
    {
        $keyName = $this->getPk();
        $descendant = $this->getQualifiedDescendantColumn();
        $ids = $this->getDescendantsAndSelf([$keyName])->pluck($keyName)->toArray();
        $root = $this->getRoot() ?: $this;
        return $root
            ->joinRelationBy('descendant', true)
            ->whereNotIn($descendant, $ids);
    }

    /**
     * @param array $columns
     * @return mixed
     */
    public function getBesides(array $columns = ['*'])
    {
        return $this->queryBesides()->field($columns)->select();
    }

    /**
     * @return mixed
     */
    public function queryChildren()
    {
        $key = $this->getKey();
        return $this->where($this->getParentColumn(), $key);
    }

    /**
     * @param array $columns
     * @return mixed
     */
    public function getChildren(array $columns = ['*'])
    {
        return $this->queryChildren()->field($columns)->select();
    }

    /**
     * @param array $columns
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getParents(array $columns = ['*'])
    {
        return $this->where($this->getPk(), $this->getParentKey())->field($columns)->fetchSql(false)->find();
    }

    /**
     * @param array $columns
     * @return mixed
     */
    public function getRoot(array $columns = ['*'])
    {
        $parentColumn = $this->getParentColumn();
        return $this
            ->joinRelationBy('ancestor')
            ->where($parentColumn, 0)
            ->whereOr(static function ($query) use ($parentColumn) {
                $query->whereNull($parentColumn);
            })
            ->field($columns)
            ->find();
    }

    /**
     * @param $child
     * @return bool
     */
    public function isParentOf($child): bool
    {
        $model = $this->parameter2Model($child);
        return $this->getKey() === $model->getParentKey();
    }

    /**
     * @param $parent
     * @return bool
     */
    public function isChildOf($parent): bool
    {
        $model = $this->parameter2Model($parent);
        return $model->getKey() === $this->getParentKey();
    }

    /**
     * @param $descendant
     * @return bool
     */
    public function isAncestorOf($descendant): bool
    {
        $keyName = $this->getPk();
        $model = $this->parameter2Model($descendant);
        $ids = $this->getDescendants([$keyName])->pluck($keyName)->toArray();
        return in_array($model->getKey(), $ids, true);
    }

    /**
     * @param $beside
     * @return bool
     */
    public function isBesideOf($beside): bool
    {
        if ($this->isRoot()) {
            return false;
        }
        $keyName = $this->getPk();
        $model = $this->parameter2Model($beside);
        $ids = $this->getDescendantsAndSelf([$keyName])->pluck($keyName)->toArray();
        return !in_array($model->getKey(), $ids, true);
    }

    /**
     * @param $ancestor
     * @return bool
     */
    public function isDescendantOf($ancestor): bool
    {
        $keyName = $this->getPk();
        $model = $this->parameter2Model($ancestor);
        $ids = $this->getAncestors([$keyName])->pluck($keyName)->toArray();
        return in_array($model->getKey(), $ids, true);
    }

    /**
     * @param $sibling
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function isSiblingOf($sibling): bool
    {
        $keyName = $this->getPk();
        $model = $this->parameter2Model($sibling);
        $ids = $this->getSiblings([$keyName])->pluck($keyName)->toArray();
        return in_array($model->getKey(), $ids, true);
    }

    /**
     * @param array $sort
     * @param string $childrenColumn
     * @param array $columns
     * @return mixed
     */
    public function getTree(array $sort = [], $childrenColumn = 'children', array $columns = ['*'])
    {
        $keyName = $this->getPk();
        $parentColumn = $this->getParentColumn();

        if (in_array('*', $columns, true)) {
            $columns = ['*'];
        } elseif (!in_array($parentColumn, $columns, true)) {
            $columns[] = $parentColumn;
        }

        if (!empty($sort)) {
            $sortKey = $sort[0] ?? 'sort';
            $sortMode = $sort[1] ?? 'asc';
            return $this
                ->joinRelationBy('descendant', true)
                ->order($sortKey, $sortMode)
                ->field($columns)
                ->select()
                ->toTree($keyName, $parentColumn, $childrenColumn);
        }
        return $this->newCollection($this
            ->joinRelationBy('descendant', true)
            ->field($columns)
            ->select())
            ->toTree($keyName, $parentColumn, $childrenColumn);
    }

    /**
     * @param array $sort
     * @param string $childrenColumn
     * @param array $columns
     * @return array
     */
    public function getBesideTree(array $sort = [], $childrenColumn = 'children', array $columns = ['*']): array
    {
        if ($this->isRoot()) {
            return [];
        }
        $keyName = $this->getPk();
        $parentColumn = $this->getParentColumn();

        if (in_array('*', $columns, true)) {
            $columns = ['*'];
        } elseif (!in_array($parentColumn, $columns, true)) {
            $columns[] = $parentColumn;
        }

        if (!empty($sort)) {
            $sortKey = $sort[0] ?? 'sort';
            $sortMode = $sort[1] ?? 'asc';
            return $this
                ->queryBesides()
                ->order($sortKey, $sortMode)
                ->field($columns)
                ->select()
                ->toTree($keyName, $parentColumn, $childrenColumn);
        }
        return $this
            ->queryBesides()
            ->field($columns)
            ->select()
            ->toTree($keyName, $parentColumn, $childrenColumn);
    }

    /**
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function querySiblings()
    {
        if ($this->getParentKey()) {
            $parent = $this->getParents();
            $key = $this->getKey();
            $keyName = $this->getPk();
            return $parent->queryChildren()->whereNotIn($keyName, [$key]);
        } else {
            return self::onlyRoot();
        }

    }

    /**
     * @param array $columns
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSiblings(array $columns = ['*'])
    {
        return $this->querySiblings()->field($columns)->select();
    }

    /**
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function querySiblingsAndSelf()
    {
        if ($this->getParentKey()) {
            $parent = $this->getParents();
            return $parent->queryChildren();
        } else {
            return self::onlyRoot();
        }

    }

    /**
     * @param array $columns
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSiblingsAndSelf(array $columns = ['*'])
    {
        return $this->querySiblingsAndSelf()->field($columns)->select();
    }

    /**
     * 这个模型是根
     *
     * @return bool
     */
    public function isRoot(): bool
    {
        return !$this->getParentKey();
    }

    /**
     * 这个模型是leaf
     *
     * @return bool
     */
    public function isLeaf(): bool
    {
        return $this->queryChildren()->count() === 0;
    }

    /**
     * 孤立节点
     * @return bool
     */
    public function isIsolated(): bool
    {
        $key = $this->getKey();
        $keyName = $this->getPk();
        $ids = $this->joinWithoutClosure()->field($keyName)->select()->pluck($keyName)->toArray();
        return in_array($key, $ids, true);
    }

    /**
     * 获取独立项
     *
     * @param array $columns
     * @return mixed
     */
    public static function getIsolated(array $columns = ['*'])
    {
        return self::isolated()->field($columns)->select();
    }

    /**
     * @param array $columns
     * @return mixed
     */
    public static function getRoots(array $columns = ['*'])
    {
        return self::onlyRoot()->field($columns)->select();
    }

    /**
     * @param  $models
     * @return CollectionExtension
     */
    public function newCollection($models = []): CollectionExtension
    {
        return new CollectionExtension($models);
    }

    /**
     * @title  闭包表初始化
     * @desc   方法描述
     * @param string $id
     * @param string $parent
     * @param string $name
     * @author HuangSen
     * DateTime: 2020/5/21 15:27
     */
    public static function initClosure($id = 'id', $parent = 'parentid', $name = 'name'): void
    {
        $data = (new CollectionExtension(self::select()->toArray()))->toTree($id, $parent);
        self::_initClosure($data, $id, $children = 'children',$name);
    }

    /**
     * @title  私有方法  _initClosure
     * @desc   方法描述
     * @param $data
     * @param string $id
     * @param string $children
     * @param string $name
     * @author HuangSen
     * DateTime: 2020/5/21 15:28
     */
    public static function _initClosure($data, $id = 'id', $children = 'children', $name = 'name'): void
    {
        foreach ($data as $v) {
            self::find($v[$id])->perfectNode();
            dump('开始：' . $v[$name]);
            if (!empty($v[$children])) {
                self::_initClosure($v[$children], $id, $children, $name);
            }
            dump('结束：' . $v[$name]);
        }
    }
}
