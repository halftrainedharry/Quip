<?php
/**
 * Preview a comment
 */
namespace Quip\Processors\Web\Comment;

use MODX\Revolution\modX;
use Quip\Quip;
use Quip\Snippets\BaseSnippet;
use Quip\StopForumSpam;

class Preview {
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
        /* make sure not empty */
        if (empty($fields['comment'])) $errors['comment'] = $this->modx->lexicon('quip.message_err_ns');

        /* verify against spam */
        $sfspam = new StopForumSpam($this->modx);
        $spamResult = $sfspam->check($_SERVER['REMOTE_ADDR'], $fields['email']);
        if (!empty($spamResult)) {
            $spamFields = implode($this->modx->lexicon('quip.spam_marked')."\n<br />", $spamResult);
            $errors['email'] = $this->modx->lexicon('quip.spam_blocked', [
                'fields' => $spamFields
            ]);
        }

        /* if requireAuth */
        if ($this->snippet->getProperty('requireAuth', false) && !$this->snippet->hasAuth) {
            $errors['comment'] = $this->modx->lexicon('quip.err_not_logged_in');
            return $errors;
        }

        /* if using reCaptcha */
        $disableRecaptchaWhenLoggedIn = $this->snippet->getProperty('disableRecaptchaWhenLoggedIn', true, 'isset');
        if ($this->snippet->getProperty('recaptcha', false) && !($disableRecaptchaWhenLoggedIn && $this->snippet->hasAuth)) {
            /* prevent having to do recaptcha more than once */
            $passedNonce = false;
            if (!empty($fields['auth_nonce'])) {
                $passedNonce = $this->quip->checkNonce($fields['auth_nonce'], 'quip-form-');
            }
            if (!$passedNonce) {
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

        /* strip tags */
        $body = $this->quip->cleanse($fields['comment']);
        $formattedBody = nl2br($body);

        /* if no errors, add preview field */
        if (empty($errors)) {
            $preview = array_merge($fields, [
                'body' => $body,
                'comment' => $formattedBody,
                'createdon' => strftime($this->snippet->getProperty('dateFormat'), time())
            ]);
            if ($this->snippet->getProperty('useGravatar', true)) {
                $preview['md5email'] = md5($fields['email']);
                $preview['gravatarIcon'] = $this->snippet->getProperty('gravatarIcon', 'identicon');
                $preview['gravatarSize'] = $this->snippet->getProperty('gravatarSize', '50');
                $urlsep = $this->modx->getOption('xhtml_urls', $this->snippet->getProperties(), true) ? '&amp;' : '&';
                $gravatarUrl = $this->snippet->getProperty('gravatarUrl', 'http://www.gravatar.com/avatar/');
                $preview['gravatarUrl'] = $gravatarUrl . $preview['md5email'] . '?s=' . $preview['gravatarSize'] . $urlsep . 'd=' . $preview['gravatarIcon'];
            }
            if (!$this->modx->user->hasSessionContext($this->modx->context->get('key')) && !$this->snippet->getProperty('requireAuth', false)) {
                $preview['author'] = 0;
            } else {
                $preview['author'] = $this->modx->user->get('id');
            }
            if (!empty($preview['website'])) {
                if (strpos($preview['website'], 'http://') !== 0 && strpos($preview['website'], 'https://') !== 0) {
                    $preview['website'] = substr($preview['website'], strpos($preview['website'], '//'));
                    $preview['website'] = 'http://' . $preview['website'];
                }
                $preview['username'] = '<a href="' . $preview['website'] . '">'.$preview['name'] . '</a>';
            } else {
                $preview['username'] = $preview['name'];
            }
            $preview['comment'] = $this->quip->parseLinks($preview['comment'], $this->snippet->getProperties());
            $this->snippet->setPlaceholder('preview', $this->quip->getChunk($this->snippet->getProperty('tplPreview'), $preview));
            $this->snippet->setPlaceholder('can_post', true);
            $hasPreview = true;

            /* make nonce value to prevent middleman/spam/hijack attacks */
            $nonce = $this->quip->createNonce('quip-form-');
        }

        $this->snippet->setPlaceholders($fields);
        if (!empty($nonce)) {
            $this->snippet->setPlaceholder('auth_nonce', $nonce);
        }
        if (!empty($hasPreview)) {
            $this->snippet->setPlaceholder('preview_mode', 1);
        }
        $this->snippet->setPlaceholder('comment', $body);

        return $errors;
    }
}