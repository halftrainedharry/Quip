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
 * Remove a comment
 */
namespace Quip\Processors\Web\Comment;

use MODX\Revolution\modX;
use Quip\Quip;
use Quip\Snippets\BaseSnippet;
use Quip\Model\quipComment;

class Remove {
    protected $modx = null;
    protected $quip = null;
    protected $snippet = null;

    function __construct(BaseSnippet &$snippet) {
        $this->snippet = &$snippet;
        $this->modx = &$snippet->modx;
        $this->quip = &$snippet->quip;
    }

    public function process($fields) {
        $errors = [];
        if (empty($_REQUEST['quip_comment'])) {
            $errors['message'] = $this->modx->lexicon('quip.comment_err_ns');
            return $errors;
        }

        /** @var quipComment $comment */
        $comment = $this->modx->getObject(quipComment::class, $_REQUEST['quip_comment']);
        if (empty($comment)) {
            $errors['message'] = $this->modx->lexicon('quip.comment_err_nf');
            return $errors;
        }

        /* only allow authors or moderators to remove comments */
        if ($comment->get('author') != $this->modx->user->get('id') && !$this->snippet->isModerator) {
            $errors['message'] = $this->modx->lexicon('quip.comment_err_nf');
            return $errors;
        }

        $comment->set('deleted', true);
        $comment->set('deletedon', strftime('%Y-%m-%d %H:%M:%S'));
        $comment->set('deletedby', $this->modx->user->get('id'));

        if (empty($errors) && $comment->save() === false) {
            $errors['message'] = $this->modx->lexicon('quip.comment_err_remove');
        }

        return $errors;
    }
}