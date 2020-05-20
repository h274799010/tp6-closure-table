<?php
namespace hs\ClosureTable\Generators;



use think\Filesystem;

/**
 * Basic generator class.
 *
 * @package hs\ClosureTable\Generators
 */
abstract class Generator
{
    /**
     * Filesystem instance.
     *
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * Constructs the generator.
     *
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Generates files from stubs.
     *
     * @param array $options
     * @return mixed
     */
    abstract public function create(array $options);

    /**
     * Gets stub files absolute path.
     *
     * @param string $type
     * @return string
     */
    protected function getStubsPath($type): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR.'stubs'.DIRECTORY_SEPARATOR . $type;
    }

    /**
     * Get a stub file by name.
     *
     * @param string $name
     * @param string $type
     * @return string
     */
    protected function getStub($name, $type): string
    {
        if (stripos($name, '.php') === false) {
            $name .= '.php';
        }

        return @file_get_contents($this->getStubsPath($type) . DIRECTORY_SEPARATOR . $name);
    }

    /**
     * Parses a stub file replacing tags with provided values.
     *
     * @param string $stub
     * @param array $replacements
     * @return string
     */
    protected function parseStub($stub, array $replacements = []): string
    {
        foreach ($replacements as $key => $replacement) {
            $stub = str_replace('{{' . $key . '}}', $replacement, $stub);
        }

        return $stub;
    }
}
