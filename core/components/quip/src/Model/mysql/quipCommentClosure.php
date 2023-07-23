<?php
namespace Quip\Model\mysql;

use xPDO\xPDO;

class quipCommentClosure extends \Quip\Model\quipCommentClosure
{

    public static $metaMap = array (
        'package' => 'Quip\\Model\\',
        'version' => '3.0',
        'table' => 'quip_comments_closure',
        'extends' => 'xPDO\\Om\\xPDOObject',
        'tableMeta' => 
        array (
            'engine' => 'InnoDB',
        ),
        'fields' => 
        array (
            'ancestor' => 0,
            'descendant' => 0,
            'depth' => 0,
        ),
        'fieldMeta' => 
        array (
            'ancestor' => 
            array (
                'dbtype' => 'int',
                'precision' => '10',
                'phptype' => 'integer',
                'attributes' => 'unsigned',
                'null' => false,
                'default' => 0,
                'index' => 'pk',
            ),
            'descendant' => 
            array (
                'dbtype' => 'int',
                'precision' => '10',
                'phptype' => 'integer',
                'attributes' => 'unsigned',
                'null' => false,
                'default' => 0,
                'index' => 'pk',
            ),
            'depth' => 
            array (
                'dbtype' => 'int',
                'precision' => '10',
                'phptype' => 'integer',
                'attributes' => 'unsigned',
                'null' => false,
                'default' => 0,
            ),
        ),
        'indexes' => 
        array (
            'PRIMARY' => 
            array (
                'alias' => 'PRIMARY',
                'primary' => true,
                'unique' => true,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'ancestor' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                    'descendant' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
        ),
        'aggregates' => 
        array (
            'Ancestor' => 
            array (
                'class' => 'Quip\\Model\\quipComment',
                'local' => 'ancestor',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
            'Descendant' => 
            array (
                'class' => 'Quip\\Model\\quipComment',
                'local' => 'descendant',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
        ),
    );

}
