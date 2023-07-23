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
 * Adds closure tables to older quip comments, and fixes their rank
 *
 * @package quip
 * @subpackage build
 */
use Quip\Model\quipComment;
use Quip\Model\quipCommentClosure;
use xPDO\Transport\xPDOTransport;

if ($transport->xpdo) {
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            $modx =& $transport->xpdo;

            $c = $modx->newQuery(quipComment::class);
            $c->sortby('thread', 'ASC');
            $comments = $modx->getCollection(quipComment::class, $c);

            foreach ($comments as $comment) {
                $id = $comment->get('id');

                $c1 = $modx->getObject(quipCommentClosure::class, [
                    'descendant' => $id,
                    'ancestor' => 0,
                ]);
                if (empty($c1)) {
                    $c1 = $modx->newObject(quipCommentClosure::class);
                    $c1->set('descendant', $id);
                    $c1->set('ancestor', 0);
                    $c1->save();
                }

                $c2 = $modx->getObject(quipCommentClosure::class, [
                    'descendant' => $id,
                    'ancestor' => $id,
                ]);
                if (empty($c2)) {
                    $c2 = $modx->newObject(quipCommentClosure::class);
                    $c2->set('descendant', $id);
                    $c2->set('ancestor', $id);
                    $c2->save();
                }

                $currentRank = $comment->get('rank');
                if (empty($currentRank)) {
                    $rank = str_pad($id, 10, '0', STR_PAD_LEFT);
                    $comment->set('rank', $rank);
                    $comment->save();
                }
            }

            break;
    }
}
return true;