<?php
namespace Quip\Model\mysql;

use xPDO\xPDO;

class quipThread extends \Quip\Model\quipThread
{

    public static $metaMap = array (
        'package' => 'Quip\\Model\\',
        'version' => '3.0',
        'table' => 'quip_threads',
        'extends' => 'xPDO\\Om\\xPDOObject',
        'tableMeta' => 
        array (
            'engine' => 'InnoDB',
        ),
        'fields' => 
        array (
            'name' => '',
            'createdon' => NULL,
            'moderated' => 1,
            'moderator_group' => 'Administrator',
            'moderators' => '',
            'notify_emails' => '',
            'resource' => 0,
            'idprefix' => 'qcom',
            'existing_params' => '{}',
            'quip_call_params' => '[]',
            'quipreply_call_params' => '[]',
        ),
        'fieldMeta' => 
        array (
            'name' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '255',
                'phptype' => 'string',
                'null' => false,
                'default' => '',
                'index' => 'pk',
            ),
            'createdon' => 
            array (
                'dbtype' => 'datetime',
                'phptype' => 'datetime',
                'null' => true,
            ),
            'moderated' => 
            array (
                'dbtype' => 'tinyint',
                'precision' => '1',
                'phptype' => 'boolean',
                'attributes' => 'unsigned',
                'null' => false,
                'default' => 1,
                'index' => 'index',
            ),
            'moderator_group' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '255',
                'phptype' => 'string',
                'null' => false,
                'default' => 'Administrator',
                'index' => 'index',
            ),
            'moderators' => 
            array (
                'dbtype' => 'text',
                'phptype' => 'string',
                'default' => '',
            ),
            'notify_emails' => 
            array (
                'dbtype' => 'text',
                'phptype' => 'string',
                'default' => '',
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
                'default' => '{}',
            ),
            'quip_call_params' => 
            array (
                'dbtype' => 'text',
                'phptype' => 'json',
                'default' => '[]',
            ),
            'quipreply_call_params' => 
            array (
                'dbtype' => 'text',
                'phptype' => 'json',
                'default' => '[]',
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
                    'name' => 
                    array (
                        'length' => '191',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
            'moderated' => 
            array (
                'alias' => 'moderated',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'moderated' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
            'moderator_group' => 
            array (
                'alias' => 'moderator_group',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'moderator_group' => 
                    array (
                        'length' => '191',
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
            'Comments' => 
            array (
                'class' => 'Quip\\Model\\quipComment',
                'local' => 'name',
                'foreign' => 'thread',
                'cardinality' => 'many',
                'owner' => 'local',
            ),
            'Notifications' => 
            array (
                'class' => 'Quip\\Model\\quipCommentNotify',
                'local' => 'name',
                'foreign' => 'thread',
                'cardinality' => 'many',
                'owner' => 'local',
            ),
        ),
        'aggregates' => 
        array (
            'Resource' => 
            array (
                'class' => 'MODX\\Revolution\\modResource',
                'local' => 'resource',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
        ),
    );

}
