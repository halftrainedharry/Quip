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
 * Report a comment
 */
namespace Quip\Processors\Web\Comment;

use MODX\Revolution\modX;
use MODX\Revolution\modUser;
use MODX\Revolution\Mail\modMail;
use MODX\Revolution\Mail\modPHPMailer;
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

        /* get comment */
        $c = $this->modx->newQuery(quipComment::class);
        $c->leftJoin(modUser::class, 'Author');
        $c->select($this->modx->getSelectColumns(quipComment::class, 'quipComment'));
        $c->select($this->modx->getSelectColumns(modUser::class, 'Author', '', ['username']));
        $c->where([
            'id' => $_REQUEST['quip_comment']
        ]);
        /** @var quipComment $comment */
        $comment = $this->modx->getObject(quipComment::class, $c);
        if ($comment == null) {
            $errors['message'] = $this->modx->lexicon('quip.comment_err_nf');
            return $errors;
        }

        $emailTo = $this->modx->getOption('quip.emailsTo', null, $this->modx->getOption('emailsender'));
        if (empty($emailTo)) {
            $errors['message'] = $this->modx->lexicon('quip.no_email_to_specified');
            return $errors;
        }

        $properties = $comment->toArray();
        $properties['url'] = $comment->makeUrl('', '', ['scheme' => 'full']);
        if (empty($properties['username'])) $properties['username'] = $comment->get('name');
        $body = $this->modx->lexicon('quip.spam_email', $properties);

        /* send spam report */
        $this->modx->getService('mail', modPHPMailer::class);
        $emailFrom = $this->modx->getOption('quip.emailsFrom', null, $emailTo);
        $emailReplyTo = $this->modx->getOption('quip.emailsReplyTo', null, $emailFrom);
        $this->modx->mail->set(modMail::MAIL_BODY, $body);
        $this->modx->mail->set(modMail::MAIL_FROM, $emailFrom);
        $this->modx->mail->set(modMail::MAIL_FROM_NAME, 'Quip');
        $this->modx->mail->set(modMail::MAIL_SENDER, 'Quip');
        $this->modx->mail->set(modMail::MAIL_SUBJECT, $this->modx->lexicon('quip.spam_email_subject'));
        $this->modx->mail->address('to', $emailTo);
        $this->modx->mail->address('reply-to', $emailReplyTo);
        $this->modx->mail->setHTML(true);
        if (!$this->modx->mail->send()) {
            //$errors['message'] = $this->modx->lexicon('error_sending_email_to') . ': ' . $emailTo;
        }
        $this->modx->mail->reset();

        return $errors;
    }
}