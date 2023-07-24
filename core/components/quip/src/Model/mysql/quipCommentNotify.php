<?php
namespace Quip\Model\mysql;

use xPDO\xPDO;

class quipCommentNotify extends \Quip\Model\quipCommentNotify
{

    public static $metaMap = array (
        'package' => 'Quip\\Model\\',
        'version' => '3.0',
        'table' => 'quip_comment_notify',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'tableMeta' => 
        array (
            'engine' => 'InnoDB',
        ),
        'fields' => 
        array (
            'thread' => '',
            'email' => '',
            'createdon' => NULL,
            'user' => 0,
        ),
        'fieldMeta' => 
        array (
            'thread' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '255',
                'phptype' => 'string',
                'null' => false,
                'default' => '',
                'index' => 'index',
            ),
            'email' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '255',
                'phptype' => 'string',
                'null' => false,
                'default' => '',
            ),
            'createdon' => 
            array (
                'dbtype' => 'datetime',
                'phptype' => 'datetime',
                'null' => true,
            ),
            'user' => 
            array (
                'dbtype' => 'integer',
                'precision' => '10',
                'phptype' => 'integer',
                'attributes' => 'unsigned',
                'null' => false,
                'default' => 0,
                'index' => 'index',
            ),
        ),
        'indexes' => 
        array (
            'thread' => 
            array (
                'alias' => 'thread',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'thread' => 
                    array (
                        'length' => '191',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
            'user' => 
            array (
                'alias' => 'user',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'user' => 
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
            'Thread' => 
            array (
                'class' => 'Quip\\Model\\quipThread',
                'local' => 'thread',
                'foreign' => 'name',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
            'Comments' => 
            array (
                'class' => 'Quip\\Model\\quipComment',
                'local' => 'thread',
                'foreign' => 'thread',
                'cardinality' => 'many',
                'owner' => 'local',
            ),
        ),
    );

}
