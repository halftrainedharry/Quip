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
 * Create a comment
 */
namespace Quip\Processors\Web\Comment;

use MODX\Revolution\modX;
use Quip\Quip;
use Quip\Snippets\BaseSnippet;
use Quip\Model\quipComment;
use Quip\Model\quipCommentNotify;
use Quip\StopForumSpam;

class Create {

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

        /* if using reCaptcha */
        $disableRecaptchaWhenLoggedIn = $this->snippet->getProperty('disableRecaptchaWhenLoggedIn', true, 'isset');
        $isLoggedIn = $this->modx->user->hasSessionContext($this->modx->context->get('key'));
        if ($this->snippet->getProperty('recaptcha', false, 'isset') && !($disableRecaptchaWhenLoggedIn && $isLoggedIn)) {
            if (isset($fields['auth_nonce']) && !empty($fields['preview_mode'])) {
                /* if already passed reCaptcha via preview mode, verify nonce to prevent spam/fraud attacks */
                $passedNonce = $this->quip->checkNonce($fields['auth_nonce'], 'quip-form-');
                if (!$passedNonce) {
                    $errors['comment'] = $this->modx->lexicon('quip.err_fraud_attempt');
                    return $errors;
                }
            } else {
                /* otherwise validate reCaptcha */
                /** @var reCaptcha $recaptcha */
                $recaptcha = $this->modx->getService('recaptcha', 'reCaptcha', $this->quip->config['modelPath'] . 'recaptcha/');
                if (!($recaptcha instanceof reCaptcha)) {
                    $errors['recaptcha'] = $this->modx->lexicon('quip.recaptcha_err_load');
                } elseif (empty($recaptcha->config[reCaptcha::OPT_PRIVATE_KEY])) {
                    $errors['recaptcha'] = $this->modx->lexicon('recaptcha.no_api_key');
                } else {
                    $response = $recaptcha->checkAnswer($_SERVER['REMOTE_ADDR'], $fields['recaptcha_challenge_field'], $fields['recaptcha_response_field']);

                    if (!$response->is_valid) {
                        $errors['recaptcha'] = $this->modx->lexicon('recaptcha.incorrect', [
                            'error' => $response->error != 'incorrect-captcha-sol' ? $response->error : ''
                        ]);
                    }
                }
            }
        }

        /* verify against spam */
        $sfspam = new StopForumSpam($this->modx);
        $spamResult = $sfspam->check($_SERVER['REMOTE_ADDR'], $fields['email']);
        if (!empty($spamResult)) {
            $spamFields = implode($this->modx->lexicon('quip.spam_marked')."\n<br />", $spamResult);
            $errors['email'] = $this->modx->lexicon('quip.spam_blocked', [
                'fields' => $spamFields,
            ]);
        }

        /* cleanse body from XSS and other junk */
        $fields['body'] = $this->quip->cleanse($fields['comment'], $this->snippet->getProperties());
        $fields['body'] = $this->quip->parseLinks($fields['body'], $this->snippet->getProperties());
        if (empty($fields['body'])) $errors['comment'] = $this->modx->lexicon('quip.message_err_ns');
        $fields['body'] = str_replace(['<br><br>', '<br /><br />'], '', nl2br($fields['body']));

        /* run preHooks */
        $this->quip->loadHooks('pre');
        $this->quip->preHooks->loadMultiple($this->snippet->getProperty('preHooks', ''), $this->snippet->getProperties(), [
            'hooks' => $this->snippet->getProperty('preHooks', ''),
        ]);

        /* if a prehook sets a field, do so here */
        $fs = $this->quip->preHooks->getFields();
        if (!empty($fs)) {
            /* better handling of checkbox values when input name is an array[] */
            foreach ($fs as $f => $v) {
                if (is_array($v)) { implode(',', $v); }
                $fs[$f] = $v;
            }
            /* assign new fields values */
            $fields = $this->quip->preHooks->getFields();
        }
        /* if any errors in preHooks */
        if (!empty($this->quip->preHooks->errors)) {
            foreach ($this->quip->preHooks->errors as $key => $error) {
                $errors[$key] = $error;
            }
        }

        /* if no errors, save comment */
        if (!empty($errors)) return $errors;

        /** @var quipComment $comment */
        $comment = $this->modx->newObject(quipComment::class);
        $comment->fromArray($fields);
        $comment->set('ip', $_SERVER['REMOTE_ADDR']);
        $comment->set('createdon', strftime('%b %d, %Y at %I:%M %p', time()));
        $comment->set('body', $fields['body']);

        /* if moderation is on, don't auto-approve comments */
        if ($this->snippet->getProperty('moderate', false, 'isset')) {
            /* by default moderate, unless special cases pass */
            $approved = false;

            /* never moderate mgr users */
            if ($this->modx->user->hasSessionContext('mgr') && $this->snippet->getProperty('dontModerateManagerUsers', true, 'isset')) {
                $approved = true;
            /* check logged in status in current context*/
            } else if ($this->modx->user->hasSessionContext($this->modx->context->get('key'))) {
                /* if moderating only anonymous users, go ahead and approve since the user is logged in */
                if ($this->snippet->getProperty('moderateAnonymousOnly', false, 'isset')) {
                    $approved = true;

                } else if ($this->snippet->getProperty('moderateFirstPostOnly', true, 'isset')) {
                    /* if moderating only first post, check to see if user has posted and been approved elsewhere.
                    * Note that this only works with logged in users.
                    */
                    $ct = $this->modx->getCount(quipComment::class, [
                        'author' => $this->modx->user->get('id'),
                        'approved' => true
                    ]);
                    if ($ct > 0) $approved = true;
                }
            }
            $comment->set('approved', $approved);
            if ($approved) {
                $comment->set('approvedon', strftime('%Y-%m-%d %H:%M:%S', time()));
            }
        }

        /* URL preservation information
        * @deprecated 0.5.0, this now goes on the Thread, will remove code in 0.5.1
        */
        if (!empty($fields['parent'])) {
            /* for threaded comments, persist the parents URL */
            /** @var quipComment $parentComment */
            $parentComment = $this->modx->getObject(quipComment::class, $fields['parent']);
            if ($parentComment) {
                $comment->set('resource', $parentComment->get('resource'));
                $comment->set('idprefix', $parentComment->get('idprefix'));
                $comment->set('existing_params', $parentComment->get('existing_params'));
            }
        } else {
            $comment->set('resource', $this->snippet->getProperty('resource', $this->modx->resource->get('id')));
            $comment->set('idprefix', $this->snippet->getProperty('idPrefix', 'qcom'));

            /* save existing parameters to comment to preserve URLs */
            $p = $this->modx->request->getParameters();
            unset($p['reported']);
            $comment->set('existing_params', $p);
        }

        /* ensure author is set */
        if ($this->snippet->hasAuth) {
            $comment->set('author', $this->modx->user->get('id'));
        }


        /* save comment */
        if ($comment->save() == false) {
            $errors['message'] = $this->modx->lexicon('quip.comment_err_save');
            return $errors;
        } elseif ($this->snippet->getProperty('requireAuth', false)) {
            /* if successful and requireAuth is true, update user profile */
            $profile = $this->modx->user->getOne('Profile');
            if ($profile) {
                if (!empty($fields['name'])) $profile->set('fullname', $fields['name']);
                if (!empty($fields['email'])) $profile->set('email', $fields['email']);
                $profile->set('website', $fields['website']);
                $profile->save();
            }
        }

        /* if comment is approved, send emails */
        if ($comment->get('approved')) {
            /** @var quipThread $thread */
            $thread = $comment->getOne('Thread');
            if ($thread) {
                if ($thread->notify($comment) == false) {
                    $this->modx->log(modX::LOG_LEVEL_ERROR, '[Quip] Notifications could not be sent for comment: '.print_r($comment->toArray(), true));
                }
            } else {
                $this->modx->log(modX::LOG_LEVEL_ERROR, '[Quip] Thread not found for comment: '.print_r($comment->toArray(), true));
            }
        } else {
            if (!$comment->notifyModerators()) {
                $this->modx->log(modX::LOG_LEVEL_ERROR, '[Quip] Moderator Notifications could not be sent for comment: '.print_r($comment->toArray(), true));
            }
        }

        /* if notify is set to true, add user to quipCommentNotify table */
        if (!empty($fields['notify'])) {
            /** @var quipCommentNotify $quipCommentNotify */
            $quipCommentNotify = $this->modx->getObject(quipCommentNotify::class, [
                'thread' => $comment->get('thread'),
                'email' => $fields['email']
            ]);
            if (empty($quipCommentNotify)) {
                $quipCommentNotify = $this->modx->newObject(quipCommentNotify::class);
                $quipCommentNotify->set('thread', $comment->get('thread'));
                $quipCommentNotify->set('email', $fields['email']);
                $quipCommentNotify->set('user', $isLoggedIn ? $this->modx->user->get('id') : 0);
                $quipCommentNotify->save();
            }
        }

        /* run postHooks */
        $commentArray = $comment->toArray();
        $this->quip->loadHooks('post');
        $this->quip->postHooks->loadMultiple($this->snippet->getProperty('postHooks', ''), $commentArray, [
            'hooks' => $this->snippet->getProperty('postHooks', '')
        ]);

        return $comment;
    }
}