<?php

/*
 * This file is part of the jonasarts Notification bundle package.
 *
 * (c) Jonas Hauser <symfony@jonasarts.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace jonasarts\Bundle\NotificationBundle\Notification;

/**
 * Notification
 */
class Notification
{
    /**
     * @var \Swift_Mailer;
     */
    private $mailer;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var string
     */
    private $kernel_root_dir;

    /**
     * configuration parameter 'from' - from address
     * 
     * @var array|string
     */
    private $from;

    /**
     * configuration parameter 'sender' - sender address
     * 
     * @var array|string
     */
    private $sender;

    /**
     * configuration parameter 'reply_to' - sender address
     * 
     * @var array|string
     */
    private $reply_to;

    /**
     * configuration parameter 'return_path'
     * 
     * - not an array !!!
     * 
     * @var string
     */
    private $return_path;

    /**
     * configuration parameter subject_prefix - subject prefix string
     * 
     * @var string
     */
    private $subject_prefix;

    /**
     * @var int
     */
    private $numSent;

    /**
     * Constructor
     */
    public function __construct(\Swift_Mailer $mailer, \Twig_Environment $twig, $kernel_root_dir, $parameter_from, $parameter_sender, $parameter_reply_to, $parameter_return_path, $parameter_subject_prefix)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->kernel_root_dir = $kernel_root_dir;

        $this->from = $parameter_from;
        $this->sender = $parameter_sender;
        $this->reply_to = $parameter_reply_to;
        $this->return_path = $parameter_return_path;

        $this->subject_prefix = $parameter_subject_prefix;

        $this->numSent = 0;
    }

    /**
     * @param \Swift_Mailer $mailer
     * @return self
     */
    public function setMailer(\Swift_Mailer $mailer)
    {
        $this->mailer = $mailer;

        return $this;
    }

    /**
     * @var \Swift_Mailer $mailer
     */
    public function getMailer()
    {
        return $this->mailer;
    }

    /**
     * @param array|string $from
     * @return self
     */
    public function setFrom($from)
    {
        $this->from = $from;

        return $this;
    }

    /**
     * @param array|string $sender
     * @return self
     */
    public function setSender($sender)
    {
        $this->sender = $sender;

        return $this;
    }

    /**
     * @param array|string $reply_to
     * @return self
     */
    public function setReplyTo($reply_to)
    {
        $this->reply_to = $reply_to;

        return $this;
    }

    /**
     * @param string $return_path
     * @return self
     */
    public function setReturnPath($return_path)
    {
        $this->return_path = $return_path;

        return $this;
    }

    /**
     * @param string $prefix
     * @return self
     */
    public function setSubjectPrefix($prefix)
    {
        $this->subject_prefix = $prefix;

        return $this;
    }

    /**
     * Send message - based on a twig template
     * 
     * @param string $template
     * @param array|string $to
     * @param array $data
     */
    public function sendTemplateMessage($template, $to, $subject, array $data)
    {
        $this->sendTemplateMessageA($template, array('to' => $to), $subject, $data);
    }

    /**
     * Send message - based on a template strings (html & txt template string)
     * 
     * @param array $templateStrings   array( html => , txt => )
     * @param array|string $to
     * @param array $data
     */
    public function sendTemplateStringMessage(array $templateStrings, $to, $subject, array $data)
    {
        $this->sendTemplateStringMessageA($templateStrings, array('to' => $to), $subject, $data);
    }

    /**
     * Send message - based on a twig template - with (optional) attachment
     * 
     * @param string $template
     * @param array $recipients    array( array|string, ... )
     * @param array|string $to
     * @param array $data
     * @param array $attachments   array w. file paths
     */
    public function sendTemplateMessageA($template, array $recipients, $subject, array $data, $attachments = array())
    {
        // auto-create variables subject & subject_underline
        if (!array_key_exists('subject', $data)) {
            $data['subject'] = $subject;
        }
        if (!array_key_exists('subject_underline', $data)) {
            $data['subject_underline'] = str_repeat('=', strlen($subject));
        }

        $html = null;
        $plain = null;

        $subject_template = $this->twig->createTemplate($subject);
        $subject = $subject_template->render($data);

        if ($this->twig->exists($template.'.html.twig')) {
            // render mail body
            $html = $this->twig->render(
                $template.'.html.twig',
                $data
                );
        }

        if ($this->twig->exists($template.'.txt.twig')) {
            // render mail body
            $plain = $this->twig->render(
                $template.'.txt.twig',
                $data
                );
        }

        if (is_null($html) && is_null($plain)) {
            return;
        }

        $this->sendMessageA($this->from, $recipients, $subject, $html, $plain, $attachments);
    }

    /**
     * Send message - based on a template strings - with (optional) attachment
     * 
     * @param array $templateStrings   array( html => , txt => )
     * @param array $recipients        array( array|string, ... )
     * @param array|string $to
     * @param array $data
     * @param array $attachments       array w. file paths
     */
    public function sendTemplateStringMessageA(array $templateStrings, array $recipients, $subject, array $data, $attachments = array())
    {
        // auto-create variables subject & subject_underline
        if (!array_key_exists('subject', $data)) {
            $data['subject'] = $subject;
        }
        if (!array_key_exists('subject_underline', $data)) {
            $data['subject_underline'] = str_repeat('=', strlen($subject));
        }

        $html = null;
        $plain = null;

        //$env = clone $this->twig;
        //$env->setCache(false);

        $loader = new \Twig_Loader_Array(array());
        $env = new \Twig_Environment($loader, array('cache' => false, 'debug' => false));

        $subject_template = $env->createTemplate($subject);
        $subject = $subject_template->render($data);

        if (is_array($templateStrings)) {
            // array with 'html' => htmlString & 'txt' => txtString twig template strings
            if (array_key_exists('html', $templateStrings)) {
                // render mail body
                $template = $env->createTemplate($templateStrings['html']);

                $html = $template->render(
                    $data
                    );
            }
            if (array_key_exists('plain', $templateStrings)) {
                // render mail body
                $template = $env->createTemplate($templateStrings['plain']);

                $plain = $template->render(
                    $data
                    );
            }
            if (array_key_exists('txt', $templateStrings)) {
                // render mail body
                $template = $env->createTemplate($templateStrings['txt']);

                $plain = $template->render(
                    $data
                    );
            }
        }

        if (is_null($html) && is_null($plain)) {
            return;
        }

        $this->sendMessageA($this->from, $recipients, $subject, $html, $plain, $attachments);
    }

    /**
     * @return int
     */
    public function sentMessagesCount()
    {
        return $this->numSent;
    }

    /**
     * @return self
     */
    public function resetMessagesCount()
    {
        $this->numSent = 0;

        return $this;
    }

    /**
     * private methods
     */

    /**
     * 
     */
    private function sendMessage($from, $to, $subject, $html = null, $plain = null)
    {
        if (!is_array($to) && trim($to) === '') {
            throw new \Exception('NotificationService.sendMessage: recipient address missing');
        }
        
        $this->sendMessageA(array('to' => $to), $subject, $html, $plain);
    }

    /**
     * 
     */
    private function sendMessageA($from, array $recipients, $subject, $html = null, $plain = null, $attachments = array())
    {
        if (!is_array($from) && trim($from) === '') {
            throw new \Exception('NotificationService.sendMessageA: from address missing');
        }
        if (count($recipients) == 0) {
            throw new \Exception('NotificationService.sendMessageA: recipient address missing');
        }
        if (trim($subject) === '') {
            throw new \Exception('NotificationService.sendMessageA: subject missing');
        }
        if (is_null($html) && is_null($plain)) {
            throw new \Exception('NotificationService.sendMessageA: content missing');
        }

        $from = $this->from;
        $sender = $this->sender;
        $reply_to = $this->reply_to;
        $return_path = $this->return_path; // not an array!

        $message = (new \Swift_Message())
            ->setFrom($from) // from
            ;

        if (!empty($sender)) {
            $message
                ->setSender($sender);
        }
        if (!empty($reply_to)) {
            $message
                ->setReplyTo($reply_to);
        }
        if (!empty($return_path)) {
            $message
                ->setReturnPath($return_path);
        }

        foreach ($recipients as $key => $value) {
            switch (strtolower($key)) {
                case 'to':
                    $message->setTo($value); // to
                    break;
                case 'cc':
                    $message->setCC($value); // cc
                    break;
                case 'bcc':
                    $message->setBCC($value); // bcc
                    break;
                default:
                    throw new \Exception('NotificationService.sendMessageA: invalid recipient type ('.$key.')');
            }
        }

        // subject (incl. prefix)
        if (empty($this->subject_prefix)) {
            $message->setSubject($subject);
        } else {
            $message->setSubject($this->subject_prefix . $subject);
        }

        /*
        // always embed logo - this needs special processing
        $file = $this->kernel_root_dir . '/../web/....png';
        $logo_src = $message->embed(\Swift_Image::fromPath($file));
        // replace logo_src placeholder in html
        $html = str_replace('%logo_src%', $logo_src, $html);
        */

        // attachment processing - optional
        foreach ($attachments as $file) {
            if (file_exists($file)) {
                if ($file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
                    $message->attach(\Swift_Attachment::fromPath($file->getRealPath()));
                } else {
                    $message->attach(\Swift_Attachment::fromPath($file));
                }
            }
        }

        if (!is_null($html)) {
            $message->setBody($html, 'text/html');
        }
        if (!is_null($plain)) {
            $message->addPart($plain, 'text/plain');
        }

        $this->numSent += $this->mailer->send($message);
    }
}
