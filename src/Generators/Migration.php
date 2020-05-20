<?php

namespace hs\ClosureTable\Generators;

use Carbon\Carbon;
use hs\ClosureTable\Extensions\Str as ExtStr;
use InvalidArgumentException;
use Phinx\Util\Util;

/**
 * ClosureTable specific migrations generator class.
 *
 * @package hs\ClosureTable\Generators
 */
class Migration extends Generator
{
    /**
     * Creates migration files.
     *
     * @param array $options
     * @return array
     */
    public function create(array $options): array
    {
        $entityClass = $this->getClassName($options['entity']);
        $entityClassFileName = Util::mapClassNameToFileName($options['entity']);
        $closureClass = $this->getClassName($options['closure']);
        //确保路径正常
        $path = $this->ensureDirectory($options['migrations-path']);
        //组装文件名
        $filePath = $path . DIRECTORY_SEPARATOR . $entityClassFileName;

        if (is_file($filePath)) {
            throw new InvalidArgumentException(sprintf('The file "%s" already exists', $filePath));
        }
//        $path = $this->getPath($options['entity-table'], $options['migrations-path']);
        $stub = $this->getStub('migration', 'migrations');
        file_put_contents(
            $filePath,
            $this->parseStub($stub, [
                'entity_table' => $options['entity-table'],
                'entity_class' => $entityClass,
                'closure_table' => $options['closure-table'],
                'closure_class' => $closureClass,
//                'innodb' => $innoDb
            ])
        );

        return [$filePath];
    }

    /**
     * Constructs migration name in Laravel style.
     *
     * @param $name
     * @return string
     */
    protected function getName($name): string
    {
//        return 'create_' . ExtStr::tableize($name) . '_table';
        return ExtStr::tableize($name);
    }

    /**
     * Constructs migration class name from the migration name.
     *
     * @param $name
     * @return string
     */
    protected function getClassName($name): string
    {
        return ExtStr::classify($this->getName($name));
    }

    /**
     * Constructs path to migration file in Laravel style.
     *
     * @param $name
     * @param $path
     * @return string
     */
    protected function getPath($name, $path): string
    {
        return $path . DIRECTORY_SEPARATOR . Carbon::now()->format('Y_m_d_His') . '_' . $this->getName($name) . '_migration.php';
    }

    /**
     * @title  确保路径存在
     * @desc   方法描述
     * @param $path
     * @return string
     * @author HuangSen
     * DateTime: 2020/5/19 9:45
     */
    private function ensureDirectory($path): string
    {

        if (!is_dir($path) && !mkdir($path, 0755, true)) {
            throw new InvalidArgumentException(sprintf('directory "%s" does not exist', $path));
        }

        if (!is_writable($path)) {
            throw new InvalidArgumentException(sprintf('directory "%s" is not writable', $path));
        }

        return $path;
    }
}
