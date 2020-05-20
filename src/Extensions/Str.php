<?php
namespace hs\ClosureTable\Extensions;

use think\helper\Str as BaseStr;

/**
 * 基本Str类的扩展.
 *
 * @package hs\ClosureTable\Extensions
 */
class Str extends BaseStr
{
    /**
     * 从给定字符串生成适当的类名.
     *
     * @param string $name
     * @return string
     */
    public static function classify($name): string
    {
//        return static::studly(static::singular($name));
        return static::studly($name);
    }

    /**
     * 从给定的类名生成数据库表名.
     *
     * @param string $name
     * @return string
     */
    public static function tableize($name)
    {
        $name = str_replace('\\', '', $name);

        return static::endsWith($name, 'Closure')
            ? static::snake($name)
            : static::snake($name);
    }
}
