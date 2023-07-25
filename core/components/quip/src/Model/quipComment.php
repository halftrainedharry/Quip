<?php
namespace Quip\Model;

use xPDO\xPDO;
use MODX\Revolution\modX;
use MODX\Revolution\modResource;
use MODX\Revolution\modUser;
use MODX\Revolution\Mail\modMail;
use Quip\Model\quipThread;
use Quip\Model\quipCommentClosure;

/**
 * Class quipComment
 *
 * @property string $thread
 * @property integer $parent
 * @property string $rank
 * @property integer $author
 * @property string $body
 * @property string $createdon
 * @property string $editedon
 * @property boolean $approved
 * @property string $approvedon
 * @property integer $approvedby
 * @property string $name
 * @property string $email
 * @property string $website
 * @property string $ip
 * @property boolean $deleted
 * @property string $deletedon
 * @property integer $deletedby
 * @property integer $resource
 * @property string $idprefix
 * @property array $existing_params
 *
 * @property \Quip\Model\quipComment[] $Children
 * @property \Quip\Model\quipCommentClosure[] $Ancestors
 * @property \Quip\Model\quipCommentClosure[] $Descendants
 *
 * @package Quip\Model
 */
class quipComment extends \xPDO\Om\xPDOSimpleObject
{
    /** @var modX|xPDO $xpdo */
    public $xpdo;
    /** @var boolean $isModerator */
    public $isModerator;
    /** @var boolean $hasAuth */
    public $hasAuth;

    /**
     * Gets the current thread
     *
     * @static
     * @param modX $modx
     * @param quipThread $thread
     * @param int $parent
     * @param string $ids
     * @param string $sortBy
     * @param string $sortByAlias
     * @param string $sortDir
     * @return array
     */
    public static function getThread(modX $modx, quipThread $thread, $parent = 0, $ids = '', $sortBy = 'rank', $sortByAlias = 'quipComment', $sortDir = 'ASC') {
        $c = $modx->newQuery(self::class);
        $c->innerJoin(quipThread::class, 'Thread');
        $c->leftJoin(quipCommentClosure::class, 'Descendants');
        $c->leftJoin(quipCommentClosure::class, 'RootDescendant', 'RootDescendant.descendant = quipComment.id AND RootDescendant.ancestor = 0');
        $c->leftJoin(quipCommentClosure::class, 'Ancestors');
        $c->leftJoin(modUser::class, 'Author');
        $c->leftJoin(modResource::class, 'Resource');
        $c->where([
            'quipComment.thread' => $thread->get('name'),
            'quipComment.deleted' => false
        ]);
        if (!$thread->checkPolicy('moderate')) {
            $c->andCondition([
                'quipComment.approved' => true
            ], null, 2);
        }
        if (!empty($parent)) {
            $c->where([
                'Ancestors.descendant' => $parent
            ]);
        }
        $total = $modx->getCount(self::class, $c);
        if (!empty($ids)) {
            $c->where([
                'Descendants.ancestor:IN' => $ids
            ]);
        }
        $c->select($modx->getSelectColumns(self::class, 'quipComment'));
        $c->select([
            'Thread.resource',
            'Thread.idprefix',
            'Thread.existing_params',
            'RootDescendant.depth',
            'Author.username',
            'Resource.pagetitle',
            'Resource.context_key'
        ]);
        $c->sortby($modx->escape($sortByAlias) . '.' . $modx->escape($sortBy), $sortDir);
        $comments = $modx->getCollection(self::class, $c);
        return [
            'results' => $comments,
            'total' => $total,
        ];
    }

    /**
     * Make a custom URL For this comment.
     *
     * @param int $resource Optional. The ID of the resource to generate the comment for. Defaults to the Thread's resource.
     * @param array $params Optional. An array of REQUEST parameters to add to the URL.
     * @param array $options Optional. An array of options, which can include 'scheme' and 'idprefix'.
     * @param boolean $addAnchor Whether or not to add the idprefix+id as an anchor tag to the URL
     * @return string The generated URL
     */
    public function makeUrl($resource = 0, $params = [], $options = [], $addAnchor = true) {
        if (empty($resource)) $resource = $this->get('resource');
        if (empty($params)) $params = $this->get('existing_params');
        if (empty($params)) $params = [];
        if (empty($options['context_key'])) {
            $options['context_key'] = $this->get('context_key');
            if (empty($options['context_key'])) {
                $modresource = $this->xpdo->getObject(modResource::class, $resource);
                $options['context_key'] = $modresource->get('context_key');
            }
        }

        $scheme= $this->xpdo->context->getOption('scheme', '', $options);
        $idprefix = $this->xpdo->context->getOption('idprefix', $this->get('idprefix'), $options);
        return $this->xpdo->makeUrl($resource, $options['context_key'], $params, $scheme) . ($addAnchor ? '#' . $idprefix . $this->get('id') : '');
    }

    /**
     * Grabs all descendants of this post.
     *
     * @access public
     * @param int $depth If set, will limit to specified depth
     * @return array A collection of quipComment objects.
     */
    public function getDescendants($depth = 0) {
        $c = $this->xpdo->newQuery(self::class);
        $c->select($this->xpdo->getSelectColumns(self::class, 'quipComment'));
        $c->select([
            'Descendants.depth'
        ]);
        $c->innerJoin(quipCommentClosure::class, 'Descendants');
        $c->innerJoin(quipCommentClosure::class, 'Ancestors');
        $c->where([
            'Descendants.ancestor' => $this->get('id')
        ]);
        if ($depth) {
            $c->where([
                'Descendants.depth:<=' => $depth
            ]);
        }
        $c->sortby('quipComment.rank', 'ASC');
        return $this->xpdo->getCollection(self::class, $c);
    }

    /**
     * Overrides xPDOObject::save to handle closure table edits.
     *
     * @param boolean $cacheFlag
     * @return boolean
     */
    public function save($cacheFlag = null) {
        $new = $this->isNew();

        if ($new) {
            if (!$this->get('createdon')) {
                $this->set('createdon', strftime('%Y-%m-%d %H:%M:%S'));
            }
            $ip = $this->get('ip');
            if (empty($ip) && !empty($_SERVER['REMOTE_ADDR'])) {
                $this->set('ip', $_SERVER['REMOTE_ADDR']);
            }
        }

        $saved = parent::save($cacheFlag);

        if ($saved && $new) {
            $id = $this->get('id');
            $parent = $this->get('parent');

            /* create self closure */
            $cl = $this->xpdo->newObject(quipCommentClosure::class);
            $cl->set('ancestor', $id);
            $cl->set('descendant', $id);
            if ($cl->save() === false) {
                $this->remove();
                return false;
            }

            /* create closures and calculate rank */
            $c = $this->xpdo->newQuery(quipCommentClosure::class);
            $c->where([
                'descendant' => $parent,
                'ancestor:!=' => 0
            ]);
            $c->sortby('depth', 'DESC');
            $gparents = $this->xpdo->getCollection(quipCommentClosure::class, $c);
            $cgps = count($gparents);
            $gps = [];
            $i = $cgps;
            /** @var quipCommentClosure $gparent */
            foreach ($gparents as $gparent) {
                $gps[] = str_pad($gparent->get('ancestor'), 10, '0', STR_PAD_LEFT);
                /** @var quipCommentClosure $obj */
                $obj = $this->xpdo->newObject(quipCommentClosure::class);
                $obj->set('ancestor', $gparent->get('ancestor'));
                $obj->set('descendant', $id);
                $obj->set('depth', $i);
                $obj->save();
                $i--;
            }
            $gps[] = str_pad($id, 10, '0', STR_PAD_LEFT); /* add self closure too */

            /* add root closure */
            /** @var quipCommentClosure $cl */
            $cl = $this->xpdo->newObject(quipCommentClosure::class);
            $cl->set('ancestor', 0);
            $cl->set('descendant', $id);
            $cl->set('depth', $cgps);
            $cl->save();

            /* set rank */
            $rank = implode('-', $gps);
            $this->set('rank', $rank);
            $this->save();
        }
        return $saved;
    }

    /**
     * Load the modLexicon service
     *
     * @return boolean
     */
    protected function _loadLexicon() {
        if (!$this->xpdo->lexicon) {
            $this->xpdo->lexicon = $this->xpdo->getService('lexicon', 'modLexicon');
            if (empty($this->xpdo->lexicon)) {
                $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, '[Quip] Could not load MODx lexicon.');
                return false;
            }
        }
        return true;
    }

    /**
     * Send an email
     *
     * @param string $subject The subject of the email
     * @param string $body The body of the email to send
     * @param string $to The email address to send to
     * @return boolean
     */
    protected function sendEmail($subject, $body, $to) {
        if (!$this->_loadLexicon()) return false;
        $this->xpdo->lexicon->load('quip:emails');

        $this->xpdo->getService('mail', 'mail.modPHPMailer');
        if (!$this->xpdo->mail) return false;

        $emailFrom = $this->xpdo->context->getOption('quip.emailsFrom', $this->xpdo->context->getOption('emailsender'));
        $emailReplyTo = $this->xpdo->context->getOption('quip.emailsReplyTo', $this->xpdo->context->getOption('emailsender'));

        /* allow multiple to addresses */
        if (!is_array($to)) {
            $to = explode(',', $to);
        }

        $success = false;
        foreach ($to as $emailAddress) {
            if (empty($emailAddress) || strpos($emailAddress, '@') == false) continue;

            $this->xpdo->mail->set(modMail::MAIL_BODY, $body);
            $this->xpdo->mail->set(modMail::MAIL_FROM, $emailFrom);
            $this->xpdo->mail->set(modMail::MAIL_FROM_NAME, $this->xpdo->context->getOption('quip.emails_from_name', 'Quip'));
            $this->xpdo->mail->set(modMail::MAIL_SENDER, $emailFrom);
            $this->xpdo->mail->set(modMail::MAIL_SUBJECT, $subject);
            $this->xpdo->mail->address('to', $emailAddress);
            $this->xpdo->mail->address('reply-to', $emailReplyTo);
            $this->xpdo->mail->setHTML(true);
            $success = $this->xpdo->mail->send();
            $this->xpdo->mail->reset();
        }

        return $success;
    }

    /**
     * Approves comment and sends out notification to poster and watchers
     *
     * @param array $options
     * @return boolean True if successful
     */
    public function approve(array $options = []) {
        if (!$this->_loadLexicon()) return false;
        $this->xpdo->lexicon->load('quip:emails');

        $this->set('approved', true);
        $this->set('approvedon', strftime('%Y-%m-%d %H:%M:%S'));
        $this->set('approvedby', $this->xpdo->user->get('id'));

        /* first attempt to save/approve */
        if ($this->save() === false) {
            return false;
        }

        /* send email to poster saying their comment was approved */
        $properties = $this->toArray();
        $properties['url'] = $this->makeUrl('', [], ['scheme' => 'full']);
        $body = $this->xpdo->lexicon('quip.email_comment_approved', $properties);
        $subject = $this->xpdo->lexicon('quip.email_comment_approved_subject');
        $this->sendEmail($subject, $body, $this->get('email'));

        /** @var quipThread $thread */
        $thread = $this->getOne('Thread');
        return $thread ? $thread->notify($this) : true;
    }

    /**
     * Reject a comment
     *
     * @param array $options
     * @return boolean True if successful
     */
    public function reject(array $options = []) {
        $this->set('deleted', true);
        $this->set('deletedon', strftime('%Y-%m-%d %H:%M:%S'));
        $this->set('deletedby', $this->xpdo->user->get('id'));

        return $this->save();
    }

    /**
     * Unapprove a comment
     * @param array $options
     * @return bool
     */
    public function unapprove(array $options = []) {
        $this->set('approved', false);
        $this->set('approvedon', null);
        $this->set('approvedby', 0);
        return $this->save();
    }

    /**
     * "Delete" a comment
     * @param array $options
     * @return boolean
     */
    public function delete(array $options = []) {
        $this->set('deleted', true);
        $this->set('deletedon', strftime('%Y-%m-%d %H:%M:%S'));
        $this->set('deletedby', $this->xpdo->user->get('id'));
        return $this->save();
    }

    /**
     * "Undelete" a comment
     * @param array $options
     * @return boolean
     */
    public function undelete(array $options = []) {
        $this->set('deleted', false);
        $this->set('deletedon', null);
        $this->set('deletedby', 0);
        return $this->save();
    }

    /**
     * Sends notification email to moderators telling them the comment is awaiting approval.
     *
     * @return boolean True if successful
     */
    public function notifyModerators() {
        if (!$this->_loadLexicon()) return false;
        $this->xpdo->lexicon->load('quip:emails');
        /** @var quipThread $thread */
        $thread = $this->getOne('Thread');
        if (!$thread) return false;

        $properties = $this->toArray();
        $properties['url'] = $this->makeUrl('', [], ['scheme' => 'full']);

        $managerUrl = MODX_URL_SCHEME . MODX_HTTP_HOST . MODX_MANAGER_URL;
        $properties['approveUrl'] = $managerUrl . '?a=home&namespace=quip' . '&quip_unapproved=1&quip_approve=' . $this->get('id');
        $properties['rejectUrl'] = $managerUrl . '?a=home&namespace=quip' . '&quip_unapproved=1&quip_reject=' . $this->get('id');
        $properties['unapprovedUrl'] = $managerUrl . '?a=home&namespace=quip' . '&quip_unapproved=1';

        $body = $this->xpdo->lexicon('quip.email_moderate', $properties);
        $subject = $this->xpdo->lexicon('quip.email_moderate_subject');

        $success = true;
        $moderators = $thread->getModeratorEmails();
        if (!empty($moderators)) {
            $success = $this->sendEmail($subject, $body, $moderators);
        }

        return $success;
    }

    /**
     * Prepare the comment for rendering
     *
     * @param array $properties
     * @param int $idx
     * @return array
     */
    public function prepare(array $properties = [], $idx, &$quip) {
        $alt = $idx % 2;
        $commentArray = $this->toArray();
        $commentArray['children'] = '';
        $commentArray['alt'] = $alt ? $this->getOption('altRowCss', $properties) : '';
        $commentArray['createdon'] = strftime($this->getOption('dateFormat', $properties), strtotime($this->get('createdon')));
        $commentArray['url'] = $this->makeUrl();
        $commentArray['idx'] = $idx;
        $commentArray['threaded'] = $this->getOption('threaded', $properties, true);
        $commentArray['depth'] = $this->get('depth');
        $commentArray['depth_margin'] = $this->getOption('useMargins', $properties, false) ? (int)($this->getOption('threadedPostMargin', $properties, '15') * $this->get('depth')) + 7 : '';
        $commentArray['cls'] = $this->getOption('rowCss', $properties, '') . ($this->get('approved') ? '' : ' ' . $this->getOption('unapprovedCls', $properties, 'quip-unapproved'));
        $commentArray['olCls'] = $this->getOption('olCss', $properties, '');
        if ($this->getOption('useGravatar', $properties, true)) {
            $commentArray['md5email'] = md5($this->get('email'));
            $commentArray['gravatarIcon'] = $this->getOption('gravatarIcon', $properties, 'mm');
            $commentArray['gravatarSize'] = $this->getOption('gravatarSize', $properties, 60);
            $urlsep = $this->xpdo->context->getOption('xhtml_urls', true) ? '&amp;' : '&';
            $commentArray['gravatarUrl'] = $this->getOption('gravatarUrl', $properties) . $commentArray['md5email'] . '?s=' . $commentArray['gravatarSize'] . $urlsep . 'd=' . $commentArray['gravatarIcon'];
        } else {
            $commentArray['gravatarUrl'] = '';
        }

        /* check for auth */
        if ($this->hasAuth) {
            /* allow removing of comment if moderator or own comment */
            $commentArray['allowRemove'] = $this->getOption('allowRemove', $properties, true);
            if ($commentArray['allowRemove']) {
                if ($this->isModerator) {
                    /* Always allow remove for moderators */
                    $commentArray['allowRemove'] = true;
                } else if ($this->get('author') == $this->xpdo->user->get('id')) {
                    /* if not moderator but author of post, check for remove
                     * threshold, which prevents removing comments after X minutes
                     */
                    $removeThreshold = $this->getOption('removeThreshold', $properties, 3);
                    if (!empty($removeThreshold)) {
                        $diff = time() - strtotime($this->get('createdon'));
                        if ($diff > ($removeThreshold * 60)) $commentArray['allowRemove'] = false;
                    }
                }
            }

            $commentArray['reported'] = !empty($_GET['reported']) && $_GET['reported'] == $this->get('id') ? 1 : '';
            if ($this->get('author') == $this->xpdo->user->get('id') || $this->isModerator) {
                $params = $this->xpdo->request->getParameters();
                $params['quip_comment'] = $this->get('id');
                $params[$this->getOption('removeAction', $properties, 'quip-remove')] = true;
                $commentArray['removeUrl'] = $this->makeUrl('', $params, null, false);
                $commentArray['options'] = $quip->getChunk($this->getOption('tplCommentOptions', $properties), $commentArray);
            } else {
                $commentArray['options'] = '';
            }

            if ($this->getOption('allowReportAsSpam', $properties, true)) {
                $params = $this->xpdo->request->getParameters();
                $params['quip_comment'] = $this->get('id');
                $params[$this->getOption('reportAction', $properties, 'quip-report')] = true;
                $commentArray['reportUrl'] = $this->makeUrl('', $params, null, false);
                $commentArray['report'] = $quip->getChunk($this->getOption('tplReport', $properties), $commentArray);
            }
        } else {
            $commentArray['report'] = '';
        }


        /* get author display name */
        $authorTpl = $this->getOption('authorTpl', $properties, 'quipAuthorTpl');
        $nameField = $this->getOption('nameField', $properties, 'username');
        $commentArray['authorName'] = '';
        if (empty($commentArray[$nameField])) {
            $commentArray['authorName'] = $quip->getChunk($authorTpl, [
                'name' => $this->getOption('showAnonymousName', false)
                    ? $this->getOption('anonymousName', $this->xpdo->lexicon('quip.anonymous'))
                    : $commentArray['name'],
                'url' => ''
            ]);
        } else {
            $commentArray['authorName'] = $quip->getChunk($authorTpl, [
                'name' => $commentArray[$nameField],
                'url' => ''
            ]);
        }

        if ($this->getOption('showWebsite', $properties, true) && !empty($commentArray['website'])) {
            $commentArray['authorName'] = $quip->getChunk($authorTpl, [
                'name' => $commentArray[$nameField],
                'url' => $commentArray['website']
            ]);
        }

        if ($this->getOption('threaded', $properties, true) && $this->getOption('stillOpen', $properties, true)
            && $this->get('depth') < $this->getOption('maxDepth', $properties, 10) && $this->get('approved')
            && !$this->getOption('closed', $properties, false)) {

            if (!$this->getOption('requireAuth', $properties, false) || $this->hasAuth) {
                $params = $this->xpdo->request->getParameters();
                $params['quip_thread'] = $this->get('thread');
                $params['quip_parent'] = $this->get('id');
                $commentArray['replyUrl'] = $this->xpdo->makeUrl($this->xpdo->getOption('replyResourceId', $properties, 1, true), '', $params);
            }
        } else {
            $commentArray['replyUrl'] = '';
        }
        return $commentArray;
    }
}
