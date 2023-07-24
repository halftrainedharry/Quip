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
 * Get a list of notifications for a thread
 *
 * @package quip
 * @subpackage processors
 */
namespace Quip\Processors\Mgr\Notification;

use MODX\Revolution\Processors\Model\GetListProcessor;
use xPDO\Om\xPDOQuery;
use xPDO\Om\xPDOObject;
use Quip\Model\quipCommentNotify;
use Quip\Model\quipThread;

class GetList extends GetListProcessor {
    public $classKey = quipCommentNotify::class;
    public $objectType = 'quip.notification';
    public $defaultSortField = 'thread';
    public $languageTopics = ['quip:default'];

    public function prepareQueryBeforeCount(xPDOQuery $c) {
        $c->leftJoin(quipThread::class, 'Thread');

        $c->where([
            'quipCommentNotify.thread:=' => $this->getProperty('thread')
        ]);

        $search = $this->getProperty('search');
        if ($search) {
            $c->where([
                'quipCommentNotify.email:LIKE' => '%' . $search . '%'
            ], null, 2);
        }
        return $c;
    }

    public function prepareQueryAfterCount(xPDOQuery $c) {
        $c->select($this->modx->getSelectColumns(quipCommentNotify::class, 'quipCommentNotify'));
        $c->select([
            'Thread.notify_emails'
        ]);
        return $c;
    }

    /**
     * @param xPDOObject|quipCommentNotify $object
     * @return boolean
     */
    public function prepareRow(xPDOObject $object) {
        $notifyArray = $object->toArray();
        $notifyArray['cls'] = '';
        $notifyArray['createdon'] = !empty($notifyArray['createdon']) ? strftime('%b %d, %Y %H:%M %p', strtotime($notifyArray['createdon'])) : '';
        return $notifyArray;
    }
}