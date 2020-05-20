<?php

use think\migration\Migrator;
use think\migration\db\Column;

class {{entity_class}} extends Migrator
{
    public function change()
    {
//        $this->table('{{entity_table}}')
//            ->addColumn('parent_id','integer',['limit'=>2,'null'=>false,'default'=>0,'comment'=>'父级id'])
//            ->addColumn('position','integer',['limit'=>2,'null'=>false,'default'=>0,'comment'=>'位置信息或者排序'])
//            ->create();

        $this->table('{{closure_table}}',['id'=>false,'primary_key' => ['ancestor','descendant']])
            ->addColumn('ancestor','integer',['limit'=>2,'null'=>false,'default'=>0,'comment'=>'祖先节点'])
            ->addColumn('descendant','integer',['limit'=>2,'null'=>false,'default'=>0,'comment'=>'后代节点'])
            ->addColumn('depth','integer',['limit'=>2,'null'=>false,'default'=>0,'comment'=>'距离'])
            ->create();
    }
}
