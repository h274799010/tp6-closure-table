<?php
declare (strict_types=1);

namespace hs\ClosureTable\Console;

use hs\ClosureTable\Extensions\Str as ExtStr;
use hs\ClosureTable\Generators\Migration;
use hs\ClosureTable\Generators\Model;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

/**
 * ClosureTable scaffolding命令，创建迁移和模型
 * @package hs\ClosureTable\Console
 */
class MakeCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'closuretable:make';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scaffolds new migrations and models suitable for ClosureTable.';

    /**
     * Migrations generator instance.
     *
     * @var Migration
     */
    private $migrator;

    /**
     * Models generator instance.
     *
     * @var Model
     */
    private $modeler;

    /**
     * User input arguments.
     *
     * @var array
     */
    private $options;


    /**
     * Creates a new command instance.
     *
     * @param Migration $migrator
     * @param Model $modeler
     */
    public function __construct(Migration $migrator, Model $modeler)
    {
        parent::__construct();

        $this->migrator = $migrator;
        $this->modeler = $modeler;
    }

    protected function configure()
    {
        // 指令配置
        $this->setName('closuretable:make')
            ->addArgument('entity', Argument::REQUIRED, '实体模型的类名')
            ->addOption('namespace', 'ns', Option::VALUE_OPTIONAL, '实体和闭包类的命名空间。一旦给定，它将重写实体和闭包模型的名称空间')
            ->addOption('entity-table', 'et', Option::VALUE_OPTIONAL, '实体的数据库表名')
            ->addOption('closure', 'c', Option::VALUE_OPTIONAL, '闭包（关系）模型的类名')
            ->addOption('closure-table', 'ct', Option::VALUE_OPTIONAL, '闭包的数据库表名（关系）')
            ->addOption('models-path', 'mdl', Option::VALUE_OPTIONAL, '放置生成模型的目录')
            ->addOption('migrations-path', 'mgr', Option::VALUE_OPTIONAL, '放置生成的迁移的目录')
            ->addOption('use-innodb', 'i', Option::VALUE_OPTIONAL, '使用InnoDB引擎（仅限MySQL）')
            ->setDescription('适合新的迁移和模型脚手架');
    }

    /**
     * 执行控制台命令
     * @param Input $input
     * @param Output $output
     * @return void
     */
    protected function execute(Input $input, Output $output): void
    {
        $this->prepareOptions($input);
        $this->writeMigrations($output);
//        $this->writeModels($output);
        // 指令输出
//        $output->writeln('test');
    }

    /**
     * 将迁移文件写入磁盘.
     *
     * @param Output $output
     * @return void
     */
    protected function writeMigrations(Output $output): void
    {
        $files = $this->migrator->create($this->options);

        foreach ($files as $file) {
            $path = pathinfo($file, PATHINFO_FILENAME);
            $output->writeln("<fg=green;options=bold>create</fg=green;options=bold>  $path");
        }

//        $this->composer->dumpAutoloads();
    }

    /**
     * 将模型文件写入磁盘。
     *
     * @param Output $output
     * @return void
     */
    protected function writeModels(Output $output): void
    {
        $files = $this->modeler->create($this->options);

        foreach ($files as $file) {
            $path = pathinfo($file, PATHINFO_FILENAME);
            $output->writeln("<fg=green;options=bold>create</fg=green;options=bold>  $path");
        }
    }

    protected function getArguments()
    {
        return [
            ['entity', Argument::REQUIRED, 'Class name of the entity model']
        ];
    }

    /**
     * 获取控制台命令选项.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['namespace', 'ns', Option::VALUE_OPTIONAL, 'Namespace for entity and closure classes. Once given, it will override namespaces of the models of entity and closure'],
            ['entity-table', 'et', Option::VALUE_OPTIONAL, 'Database table name for entity'],
            ['closure', 'c', Option::VALUE_OPTIONAL, 'Class name of the closure (relationships) model'],
            ['closure-table', 'ct', Option::VALUE_OPTIONAL, 'Database table name for closure (relationships)'],
            ['models-path', 'mdl', Option::VALUE_OPTIONAL, 'Directory in which to put generated models'],
            ['migrations-path', 'mgr', Option::VALUE_OPTIONAL, 'Directory in which to put generated migrations'],
            ['use-innodb', 'i', Option::VALUE_OPTIONAL, 'Use InnoDB engine (MySQL only)'],
        ];
    }

    /**
     * 准备要传递给migrator和modeler实例的用户输入选项。
     * @param Input $inputs
     * @return void
     */
    protected function prepareOptions(Input $inputs): void
    {
        $entity = $inputs->getArgument('entity');
        $options = $this->getOptions();
        $input = array_map(static function (array $option) use ($inputs) {
            return $inputs->getOption($option[0]);
        }, $this->getOptions());

        $this->options[$options[0][0]] = $this->getNamespace($entity, $input[0]);
        $this->options['entity'] = $this->getEntityModelName($entity);
        $this->options[$options[1][0]] = $input[1] ?: ExtStr::tableize($this->options['entity']);
        $this->options[$options[2][0]] = $input[2]
            ? $this->getEntityModelName($input[2])
            : $this->options['entity'] . 'Closure';

        $this->options[$options[3][0]] = $input[3] ?: ExtStr::snake($this->options[$options[2][0]]);
        $this->options[$options[4][0]] = $input[4] ?: app_path();
        $this->options[$options[5][0]] = $input[5] ?: $this->database_path('migrations');
        $this->options[$options[6][0]] = $input[6] ?: true;
    }

    private function getNamespace($entity, $original)
    {
        if (!empty($original)) {
            return $original;
        }
        if ($end = strrpos($entity, '\\')) {
            $namespace = substr($entity, 0, $end);
        }

        if (!empty($namespace)) {
            return $namespace;
        }

        return rtrim(app()->getNamespace(), '\\');
    }

    private function getEntityModelName($original)
    {
        $delimpos = strrpos($original, '\\');

        if ($delimpos === false) {
            return $original;
        }

        return substr($original, $delimpos + 1);
    }

    /**
     * 获取数据迁移脚本地址
     * @param string $path
     * @return string
     */
    private function database_path(string $path = ''): string
    {
        return app()->getRootPath() . 'database' . DIRECTORY_SEPARATOR . $path;
    }

}
