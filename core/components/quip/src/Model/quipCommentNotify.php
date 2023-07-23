<?php
namespace Quip\Model;

use xPDO\xPDO;

/**
 * Class quipCommentNotify
 *
 * @property string $thread
 * @property string $email
 * @property string $createdon
 * @property integer $user
 *
 * @package Quip\Model
 */
class quipCommentNotify extends \xPDO\Om\xPDOSimpleObject
{
    /** @var modX $modx */
    public $xpdo;

    public function save($cacheFlag= null) {
        if ($this->isNew()) {
            $this->set('createdon',strftime('%Y-%m-%d %H:%M:%S'));
        }
        return parent::save($cacheFlag);
    }

    /**
     * Send the notification
     *
     * @param quipComment $comment
     * @param array $properties
     * @return boolean
     */
    public function send(quipComment $comment,array $properties = array()) {
        $this->xpdo->getService('mail', 'mail.modPHPMailer');
        if (!$this->xpdo->mail) return false;
        $email = $this->get('email');

        /* set unsubscription link */
        $unsubscribeSecretHash = 'One sees great things from the valley, only small things from the peak.';
        $hash = md5('quip.'.$unsubscribeSecretHash.$email.$this->get('createdon'));
        $properties['unsubscribeUrl'] = $comment->makeUrl('',array(
            'quip_unsub' => $email,
            'quip_uhsh' => $hash,
        ),array(
            'scheme' => 'full',
        ),false).'#quip-success-'.$comment->get('idprefix');
        $properties['unsubscribeText'] = $this->xpdo->lexicon('quip.unsubscribe_text',array(
            'unsubscribeUrl' => $properties['unsubscribeUrl'],
        ));

        $body = $this->xpdo->lexicon('quip.email_notify',$properties);
        $subject = $this->xpdo->lexicon('quip.email_notify_subject');
        $emailFrom = $this->xpdo->context->getOption('quip.emailsFrom',$this->xpdo->context->getOption('emailsender'));
        $emailReplyTo = $this->xpdo->context->getOption('quip.emailsReplyTo',$this->xpdo->context->getOption('emailsender'));
        if (empty($email) || strpos($email,'@') == false) return false;

        if ($this->xpdo->parser) {
            $this->xpdo->parser->processElementTags('',$body,true,false);
            $this->xpdo->parser->processElementTags('',$subject,true,false);
            $this->xpdo->parser->processElementTags('',$emailFrom,true,false);
            $this->xpdo->parser->processElementTags('',$emailReplyTo,true,false);
        }

        $this->xpdo->mail->set(modMail::MAIL_BODY,$body);
        $this->xpdo->mail->set(modMail::MAIL_FROM,$emailFrom);
        $this->xpdo->mail->set(modMail::MAIL_FROM_NAME,$this->xpdo->context->getOption('quip.emails_from_name','Quip'));
        $this->xpdo->mail->set(modMail::MAIL_SENDER,$emailFrom);
        $this->xpdo->mail->set(modMail::MAIL_SUBJECT,$subject);
        $this->xpdo->mail->address('to',$email);
        $this->xpdo->mail->address('reply-to',$emailReplyTo);
        $this->xpdo->mail->setHTML(true);
        $success = $this->xpdo->mail->send();
        $this->xpdo->mail->reset();
        return $success;
    }
}
