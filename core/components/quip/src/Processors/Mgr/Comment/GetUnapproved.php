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
 * Get a list of comments for a thread
 *
 * @package quip
 * @subpackage processors
 */
namespace Quip\Processors\Mgr\Comment;

use MODX\Revolution\Processors\Model\GetListProcessor;
use MODX\Revolution\modResource;
use MODX\Revolution\modUser;
use xPDO\Om\xPDOQuery;
use xPDO\Om\xPDOObject;
use Quip\Model\quipComment;
use Quip\Model\quipThread;

class GetUnapproved extends GetListProcessor {
    public $classKey = quipComment::class;
    public $objectType = 'quip.thread';

    public $primaryKeyField = 'name';
    public $defaultSortField = 'createdon';
    public $defaultSortDirection = 'DESC';

    public $permission = 'quip.comment_list';
    public $checkListPermission = false;
    public $languageTopics = ['quip:default'];

    /** @var quipThread $thread */
    public $thread;
    /** @var string $defaultCls */
    public $defaultCls = '';

    public function initialize() {
        $initialized = parent::initialize();
        $this->setDefaultProperties([
            'combo' => false,
            'limit' => false,
            'deleted' => 0,
            'search' => false,
            'family' => false,
            'thread' => false
        ]);

        $thread = $this->getProperty('thread');
        if (!empty($thread)) {
            $this->thread = $this->modx->getObject(quipThread::class, $thread);
            if (empty($this->thread)) return $this->modx->lexicon('quip.thread_err_nf');
            if (!$this->thread->checkPolicy('view')) return $this->modx->lexicon('access_denied');
        }

        return $initialized;
    }

    public function prepareQueryBeforeCount(xPDOQuery $c) {
        $ugns = $this->modx->user->getUserGroupNames();
        $userGroupNames = '';
        foreach ($ugns as $ugn) {
            $userGroupNames .= '"' . $ugn . '",';
        }
        $userGroupNames = rtrim($userGroupNames, ',');

        $c->leftJoin(modUser::class, 'Author');
        $c->leftJoin(modResource::class, 'Resource');
        $c->innerJoin(quipThread::class, 'Thread');
        $c->where([
            'quipComment.deleted' => $this->getProperty('deleted', false),
            'quipComment.approved' => false
        ]);
        /* handle moderator permissions */
        $c->andCondition(['(
            Thread.moderated = 0
                OR Thread.moderator_group IN (' . $userGroupNames . ')
                OR "' . $this->modx->user->get('username') . '" IN (Thread.moderators)
        )']);
        $thread = $this->getProperty('thread');
        if (!empty($thread)) {
            $c->where([
                'quipComment.thread' => $thread
            ]);
        }
        return $c;
    }

    public function prepareQueryAfterCount(xPDOQuery $c) {
        $subc = $this->modx->newQuery(quipComment::class);
        $subc->setClassAlias('CommentCount');
        $subc->select('COUNT(CommentCount.id)');
        $subc->where('CommentCount.thread = quipComment.thread');
        $subc->where([
            'CommentCount.deleted' => 0,
            'CommentCount.approved' => 1
        ]);
        $subc->prepare();
        $commentsSql = $subc->toSql();

        $c->select($this->modx->getSelectColumns(quipComment::class, 'quipComment'));
        $c->select([
            'Author.username',
            'Resource.pagetitle',
            '(' . $commentsSql . ') AS comments'
        ]);
        return $c;
    }

    public function beforeIteration(array $list) {
        $canApprove = $this->modx->hasPermission('quip.comment_approve');
        $canRemove = $this->modx->hasPermission('quip.comment_remove');
        $canUpdate = $this->modx->hasPermission('quip.comment_update');
        if ($this->thread) {
            $canApprove = $canApprove && $this->thread->checkPolicy('comment_approve');
            $canRemove = $canRemove && $this->thread->checkPolicy('comment_remove');
            $canUpdate = $canUpdate && $this->thread->checkPolicy('comment_update');
        }
        $cls = [];
        if ($canApprove) $cls[] = 'papprove';
        if ($canUpdate) $cls[] = 'pupdate';
        if ($canRemove) $cls[] = 'premove';
        $this->defaultCls = implode(',', $cls);
        return $list;
    }

    /**
     * @param xPDOObject|quipThread $object
     * @return array
     */
    public function prepareRow(xPDOObject $object) {
        $commentArray = $object->toArray();
        $commentArray['url'] = $object->makeUrl();
        if (empty($commentArray['pagetitle'])) {
            $commentArray['pagetitle'] = $this->modx->lexicon('quip.view');
        }

        if (empty($commentArray['username'])) $commentArray['username'] = $commentArray['name'];
        $commentArray['body'] = str_replace('<br />', '', $commentArray['body']);
        $commentArray['createdon'] = strftime('%a %b %d, %Y %I:%M %p', strtotime($commentArray['createdon']));
        $commentArray['cls'] = $this->defaultCls;
        return $commentArray;
    }
}