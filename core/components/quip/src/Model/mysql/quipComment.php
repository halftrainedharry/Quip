<?php
namespace Quip\Model\mysql;

use xPDO\xPDO;

class quipComment extends \Quip\Model\quipComment
{

    public static $metaMap = array (
        'package' => 'Quip\\Model\\',
        'version' => '3.0',
        'table' => 'quip_comments',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'tableMeta' => 
        array (
            'engine' => 'InnoDB',
        ),
        'fields' => 
        array (
            'thread' => '',
            'parent' => 0,
            'rank' => NULL,
            'author' => 0,
            'body' => '',
            'createdon' => NULL,
            'editedon' => NULL,
            'approved' => 1,
            'approvedon' => NULL,
            'approvedby' => 0,
            'name' => '',
            'email' => '',
            'website' => '',
            'ip' => '0.0.0.0',
            'deleted' => 0,
            'deletedon' => NULL,
            'deletedby' => 0,
            'resource' => 0,
            'idprefix' => 'qcom',
            'existing_params' => '[]',
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
            'parent' => 
            array (
                'dbtype' => 'integer',
                'precision' => '10',
                'phptype' => 'integer',
                'attributes' => 'unsigned',
                'null' => false,
                'default' => 0,
                'index' => 'index',
            ),
            'rank' => 
            array (
                'dbtype' => 'tinytext',
                'phptype' => 'string',
            ),
            'author' => 
            array (
                'dbtype' => 'integer',
                'precision' => '10',
                'phptype' => 'integer',
                'attributes' => 'unsigned',
                'null' => false,
                'default' => 0,
                'index' => 'index',
            ),
            'body' => 
            array (
                'dbtype' => 'text',
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
            'editedon' => 
            array (
                'dbtype' => 'datetime',
                'phptype' => 'datetime',
                'null' => true,
            ),
            'approved' => 
            array (
                'dbtype' => 'tinyint',
                'precision' => '1',
                'phptype' => 'boolean',
                'attributes' => 'unsigned',
                'null' => false,
                'default' => 1,
                'index' => 'index',
            ),
            'approvedon' => 
            array (
                'dbtype' => 'datetime',
                'phptype' => 'datetime',
                'null' => true,
            ),
            'approvedby' => 
            array (
                'dbtype' => 'integer',
                'precision' => '10',
                'phptype' => 'integer',
                'attributes' => 'unsigned',
                'null' => false,
                'default' => 0,
                'index' => 'index',
            ),
            'name' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '255',
                'phptype' => 'string',
                'null' => false,
                'default' => '',
            ),
            'email' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '255',
                'phptype' => 'string',
                'null' => false,
                'default' => '',
            ),
            'website' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '255',
                'phptype' => 'string',
                'null' => false,
                'default' => '',
            ),
            'ip' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '255',
                'phptype' => 'string',
                'null' => false,
                'default' => '0.0.0.0',
            ),
            'deleted' => 
            array (
                'dbtype' => 'tinyint',
                'precision' => '1',
                'phptype' => 'boolean',
                'attributes' => 'unsigned',
                'null' => false,
                'default' => 0,
                'index' => 'index',
            ),
            'deletedon' => 
            array (
                'dbtype' => 'datetime',
                'phptype' => 'datetime',
                'null' => true,
            ),
            'deletedby' => 
            array (
                'dbtype' => 'integer',
                'precision' => '10',
                'phptype' => 'integer',
                'attributes' => 'unsigned',
                'null' => false,
                'default' => 0,
                'index' => 'index',
            ),
            'resource' => 
            array (
                'dbtype' => 'int',
                'precision' => '10',
                'phptype' => 'integer',
                'attributes' => 'unsigned',
                'null' => false,
                'default' => 0,
                'index' => 'index',
            ),
            'idprefix' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '255',
                'phptype' => 'string',
                'null' => false,
                'default' => 'qcom',
            ),
            'existing_params' => 
            array (
                'dbtype' => 'text',
                'phptype' => 'json',
                'default' => '[]',
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
            'parent' => 
            array (
                'alias' => 'parent',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'parent' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
            'author' => 
            array (
                'alias' => 'author',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'author' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
            'approved' => 
            array (
                'alias' => 'approved',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'approved' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
            'approvedby' => 
            array (
                'alias' => 'approvedby',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'approvedby' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
            'deleted' => 
            array (
                'alias' => 'deleted',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'deleted' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
            'deletedby' => 
            array (
                'alias' => 'deletedby',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'deletedby' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
            'resource' => 
            array (
                'alias' => 'resource',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'resource' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
        ),
        'composites' => 
        array (
            'Children' => 
            array (
                'class' => 'Quip\\Model\\quipComment',
                'local' => 'id',
                'foreign' => 'parent',
                'cardinality' => 'many',
                'owner' => 'local',
            ),
            'Ancestors' => 
            array (
                'class' => 'Quip\\Model\\quipCommentClosure',
                'local' => 'id',
                'foreign' => 'ancestor',
                'cardinality' => 'many',
                'owner' => 'local',
            ),
            'Descendants' => 
            array (
                'class' => 'Quip\\Model\\quipCommentClosure',
                'local' => 'id',
                'foreign' => 'descendant',
                'cardinality' => 'many',
                'owner' => 'local',
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
            'Author' => 
            array (
                'class' => 'MODX\\Revolution\\modUser',
                'local' => 'author',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
            'Resource' => 
            array (
                'class' => 'MODX\\Revolution\\modResource',
                'local' => 'resource',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
            'Parent' => 
            array (
                'class' => 'Quip\\Model\\quipComment',
                'local' => 'parent',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
        ),
    );

}
