<?php

use MODX\Revolution\modAccessContext;
use MODX\Revolution\modAccessPermission;
use MODX\Revolution\modAccessPolicy;
use MODX\Revolution\modAccessPolicyTemplate;
use MODX\Revolution\modAccessPolicyTemplateGroup;
use MODX\Revolution\modContext;
use MODX\Revolution\modUserGroup;
use MODX\Revolution\modX;
use xPDO\Transport\xPDOTransport;

/**
 * @var xPDOTransport $transport
 * @var array $object
 * @var array $options
 */

/** @var modX $modx */
$modx =& $transport->xpdo;

switch ($options[xPDOTransport::PACKAGE_ACTION]) {
    case xPDOTransport::ACTION_INSTALL:
    case xPDOTransport::ACTION_UPGRADE:

        $group = $modx->getObject(modAccessPolicyTemplateGroup::class, ['name' => 'Administrator']);
        if (!$group) return;

        /** @var modAccessPolicyTemplate $template */
        $template = $modx->getObject(modAccessPolicyTemplate::class, ['name' => 'QuipModeratorPolicyTemplate', 'template_group' => $group->get('id')]);
        if (!$template) {
            $template = $modx->newObject(modAccessPolicyTemplate::class);
            $template->set('name', 'QuipModeratorPolicyTemplate');
            $template->set('template_group', $group->get('id'));
            $template->set('description', 'A policy for moderating Quip comments.');
            $template->set('lexicon', 'quip:permissions');
            $template->save();
        }

        $permissions = [
            [
                'name' => 'quip.comment_approve',
                'description' => 'perm.comment_approve',
                'value' => true
            ],
            [
                'name' => 'quip.comment_list',
                'description' => 'perm.comment_list',
                'value' => true
            ],
            [
                'name' => 'quip.comment_list_unapproved',
                'description' => 'perm.comment_list_unapproved',
                'value' => true
            ],
            [
                'name' => 'quip.comment_remove',
                'description' => 'perm.comment_remove',
                'value' => true
            ],
            [
                'name' => 'quip.comment_update',
                'description' => 'perm.comment_update',
                'value' => true
            ],
            [
                'name' => 'quip.thread_list',
                'description' => 'perm.thread_list',
                'value' => true
            ],
            [
                'name' => 'quip.thread_manage',
                'description' => 'perm.thread_manage',
                'value' => true
            ],
            [
                'name' => 'quip.thread_remove',
                'description' => 'perm.thread_remove',
                'value' => true
            ],
            [
                'name' => 'quip.thread_truncate',
                'description' => 'perm.thread_truncate',
                'value' => true
            ],
            [
                'name' => 'quip.thread_update',
                'description' => 'perm.thread_update',
                'value' => true
            ],
            [
                'name' => 'quip.thread_view',
                'description' => 'perm.thread_view',
                'value' => true
            ]
        ];

        foreach ($permissions as $permission) {
            /** @var modAccessPermission $obj */
            $obj = $modx->getObject(modAccessPermission::class, [
                'template' => $template->get('id'),
                'name' => $permission['name']
            ]);

            if (!$obj) {
                $obj = $modx->newObject(modAccessPermission::class);
                $obj->set('template', $template->get('id'));
                $obj->set('name', $permission['name']);
                $obj->set('description', $permission['description']);
                $obj->set('value', $permission['value']);
                $obj->save();
            }
        }

        /** @var modAccessPolicy $moderatorPolicy */
        $moderatorPolicy = $modx->getObject(modAccessPolicy::class, ['name' => 'QuipModeratorPolicy']);
        if (!$moderatorPolicy) {
            $moderatorPolicy = $modx->newObject(modAccessPolicy::class);
            $moderatorPolicy->set('name', 'QuipModeratorPolicy');
            $moderatorPolicy->set('description', 'A policy for moderating Quip comments.');
            $moderatorPolicy->set('template', $template->get('id'));
            $moderatorPolicy->set('lexicon', 'quip:permissions');
        }

        $data = [];

        foreach ($permissions as $permission) {
            $data[$permission['name']] = true;
        }

        $moderatorPolicy->set('data', $data);
        $moderatorPolicy->save();

        /** @var modUserGroup $adminUserGroup */
        $adminUserGroup = $modx->getObject(modUserGroup::class, ['name' => 'Administrator']);
        if ($adminUserGroup) {

            $contextAccess = $modx->getObject(modAccessContext::class, [
                'target' => 'mgr',
                'principal_class' => modUserGroup::class,
                'principal' => $adminUserGroup->get('id'),
                'authority' => 9999,
                'policy' => $moderatorPolicy->get('id'),
            ]);
            if (!$contextAccess) {
                $contextAccess = $modx->newObject(modAccessContext::class);
                $contextAccess->fromArray([
                    'target' => 'mgr',
                    'principal_class' => modUserGroup::class,
                    'principal' => $adminUserGroup->get('id'),
                    'authority' => 9999,
                    'policy' => $moderatorPolicy->get('id'),
                ]);
                $contextAccess->save();
            }
        }

        break;
}

return true;
