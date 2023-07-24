<?php
/**
 * Quip
 *
 * Copyright 2010-11 by Shaun McCormick <shaun@modx.com>
 *
 * This file is part of Quip, a simple commenting component for MODx Revolution.
 *
 * Quip is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * Quip is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * Quip; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 *
 * @package quip
 */
/**
 * Get a list of threads
 *
 * @package quip
 * @subpackage processors
 */
namespace Quip\Processors\Mgr\Thread;

use MODX\Revolution\Processors\Model\GetListProcessor;
use MODX\Revolution\modResource;
use xPDO\Om\xPDOQuery;
use xPDO\Om\xPDOObject;
use Quip\Model\quipThread;
use Quip\Model\quipComment;

class GetList extends GetListProcessor {
    public $classKey = quipThread::class;
    public $objectType = 'quip.thread';
    public $primaryKeyField = 'name';
    public $checkListPermission = false;
    public $languageTopics = ['quip:default'];

    public function prepareQueryBeforeCount(xPDOQuery $c) {
        $c->leftJoin(modResource::class, 'Resource');

        $search = $this->getProperty('search');
        if ($search) {
            $c->where([
                'quipThread.name:LIKE' => '%' . $search . '%',
                'OR:quipThread.moderator_group:LIKE' => '%' . $search . '%',
                'OR:Resource.pagetitle:LIKE' => '%' . $search . '%'
            ], null, 2);
        }
        return $c;
    }

    public function prepareQueryAfterCount(xPDOQuery $c) {
        /* get approved comments sql */
        $subc = $this->modx->newQuery(quipComment::class);
        $subc->setClassAlias('ApprovedComments');
        $subc->select('COUNT(*)');
        $subc->where([
            'quipThread.name = ApprovedComments.thread',
            'ApprovedComments.deleted' => 0,
            'ApprovedComments.approved' => 1
        ]);
        $subc->prepare();
        $approvedCommentsSql = $subc->toSql();

        /* get unapproved comments sql */
        $subc = $this->modx->newQuery(quipComment::class);
        $subc->setClassAlias('ApprovedComments');
        $subc->select('COUNT(*)');
        $subc->where([
            'quipThread.name = ApprovedComments.thread',
            'ApprovedComments.deleted' => 0,
            'ApprovedComments.approved' => 0
        ]);
        $subc->prepare();
        $unapprovedCommentsSql = $subc->toSql();

        $c->select($this->modx->getSelectColumns(quipThread::class, 'quipThread'));
        $c->select([
            'Resource.pagetitle',
            'Resource.context_key',
            '(' . $approvedCommentsSql . ') AS comments',
            '(' . $unapprovedCommentsSql . ') AS unapproved_comments'
        ]);
        return $c;
    }

    /**
     * @param xPDOObject|quipThread $object
     * @return boolean
     */
    public function prepareRow(xPDOObject $object) {
        if (!$object->checkPolicy('view')) return false;
        $threadArray = $object->toArray();
        $resourceTitle = $object->get('pagetitle');
        if (!empty($resourceTitle)) {
            $threadArray['url'] = $object->makeUrl();
        }

        $cls = '';
        $cls .= $object->checkPolicy('truncate') ? ' truncate' : '';
        $cls .= $object->checkPolicy('remove') ? ' remove' : '';
        $threadArray['perm'] = $cls;

        return $threadArray;
    }
}