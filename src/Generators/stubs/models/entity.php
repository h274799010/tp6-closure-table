<?php
{{namespace}}

use hs\ClosureTable\Models\Entity;

class {{entity_class}} extends Entity
{
    /**
     * 与模型关联的表
     *
     * @var string
     */
    protected $table = '{{entity_table}}';

    /**
     * ClosureTable模型实例.
     *
     * @var \{{closure_class}}
     */
    protected $closure = '{{closure_class}}';
}
