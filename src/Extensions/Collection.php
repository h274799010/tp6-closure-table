<?php
namespace hs\ClosureTable\Extensions;

use think\Collection as EloquentCollection;
use hs\ClosureTable\Models\Entity;

/**
 * 扩展集合类。提供了一些有用的方法
 *
 * @method Entity|null get($key, $default = null)
 * @package hs\ClosureTable\Extensions
 */
class Collection extends EloquentCollection
{
    /**
     * 返回给定位置的子节点.
     *
     * @param int $position
     *
     * @return Entity|null
     */
    public function getChildAt($position)
    {
        return $this->filter(static function (Entity $entity) use ($position) {
            return $entity->position === $position;
        })->first();
    }

    /**
     * 返回第一个子节点.
     *
     * @return Entity|null
     */
    public function getFirstChild()
    {
        return $this->getChildAt(0);
    }

    /**
     * 返回最后一个子节点.
     *
     * @return Entity|null
     */
    public function getLastChild()
    {
        return $this->sortByDesc(static function (Entity $entity) {
            return $entity->position;
        })->first();
    }

    /**
     * 按给定位置筛选集合.
     *
     * @param int $from
     * @param int|null $to
     *
     * @return Collection
     */
    public function getRange($from, $to = null)
    {
        return $this->filter(static function (Entity $entity) use ($from, $to) {
            if ($to === null) {
                return $entity->position >= $from;
            }

            return $entity->position >= $from && $entity->position <= $to;
        });
    }

    /**
     * 筛选集合以从具有给定位置的节点返回“左”和“右”上的节点.
     *
     * @param int $position
     *
     * @return Collection
     */
    public function getNeighbors($position)
    {
        return $this->filter(static function (Entity $entity) use ($position) {
            return $entity->position === $position - 1 ||
                   $entity->position === $position + 1;
        });
    }

    /**
     * 筛选集合以返回具有给定位置的节点的以前同级.
     *
     * @param int $position
     *
     * @return Collection
     */
    public function getPrevSiblings($position)
    {
        return $this->filter(static function (Entity $entity) use ($position) {
            return $entity->position < $position;
        });
    }

    /**
     * 筛选集合以返回具有给定位置的节点的下一个同级.
     *
     * @param int $position
     *
     * @return Collection
     */
    public function getNextSiblings($position)
    {
        return $this->filter(static function (Entity $entity) use ($position) {
            return $entity->position > $position;
        });
    }

    /**
     * 检索子关系.
     *
     * @param $position
     * @return Collection
     */
    public function getChildrenOf($position)
    {
        if (!$this->hasChildren($position)) {
            return new static();
        }

        return $this->getChildAt($position)->children;
    }

    /**
     * 指示项是否有子项.
     *
     * @param $position
     * @return bool
     */
    public function hasChildren($position)
    {
        $item = $this->getChildAt($position);

        return $item !== null && $item->children->count() > 0;
    }

    /**
     * 制作树状集合.
     *
     * @return Collection
     */
    public function toTree()
    {
        $items = $this->items;

        return new static($this->makeTree($items));
    }

    /**
     * 执行实际树生成方法.
     *
     * @param Entity[] $items
     * @return array
     */
    protected function makeTree(array $items)
    {
        /** @var Entity[] $result */
        $result = [];
        $tops = [];

        foreach ($items as $item) {
            $result[$item->getKey()] = $item;
        }

        foreach ($items as $item) {
            $parentId = $item->parent_id;

            if (array_key_exists($parentId, $result)) {
                $result[$parentId]->appendChild($item);
            } else {
                $tops[] = $item;
            }
        }

        return $tops;
    }
}
