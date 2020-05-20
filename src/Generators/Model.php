<?php

namespace hs\ClosureTable\Generators;

use hs\ClosureTable\Extensions\Str as ExtStr;

/**
 * ClosureTable特定模型生成器类.
 *
 * @package hs\ClosureTable\Generators
 */
class Model extends Generator
{
    /**
     * 创建模型和接口文件.
     *
     * @param array $options
     * @return array
     */
    public function create(array $options): array
    {
        $paths = [];

        $nsplaceholder = !empty($options['namespace']) ? sprintf('namespace %s;', $options['namespace']) : '';

//        $qualifiedEntityName = $options['entity'];
        $qualifiedClosureName = $options['closure'];

//        //首先 我们创建实体类
//        $paths[] = $path = $this->getPath($qualifiedEntityName, $options['models-path']);
//
//        $stub = $this->getStub('entity', 'models');
//
        $closureClass = ucfirst($options['closure']);
//        $namespaceWithDelimiter = $options['namespace'] . '\\';
//
//        file_put_contents($path, $this->parseStub($stub, [
//            'namespace' => $nsplaceholder,
//            'entity_class' => ucfirst($options['entity']),
//            'entity_table' => env('database.prefix').$options['entity-table'],
//            'closure_class' => Str::startsWith($closureClass, $namespaceWithDelimiter)
//                ? $closureClass
//                : $namespaceWithDelimiter . $closureClass,
//        ]));

        //然后，我们创建闭包表类
        $paths[] = $path = $this->getPath($qualifiedClosureName, $options['models-path']);
        $stub = $this->getStub('closuretable', 'models');

        file_put_contents($path, $this->parseStub($stub, [
            'namespace' => $nsplaceholder,
            'closure_class' => $closureClass,
            'closure_table' => env('database.prefix').$options['closure-table']
        ]));

        return $paths;
    }

    /**
     * 构造模型的路径.
     *
     * @param $name
     * @param $path
     * @return string
     */
    protected function getPath($name, $path): string
    {
        $delimpos = strrpos($name, '\\');
        $filename = $delimpos === false
            ? ExtStr::classify($name)
            : substr(ExtStr::classify($name), $delimpos + 1);

        return $path . $filename . '.php';
    }
}
