<?php

declare(strict_types=1);

/*
 * This file is part of the jonasarts Notification bundle package.
 *
 * (c) Jonas Hauser <symfony@jonasarts.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace jonasarts\Bundle\NotificationBundle\Notification;

use jonasarts\Bundle\NotificationBundle\Notification\NotificationInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * MailerNotification - uses Symfony Mailer
 */
class MailerNotification implements NotificationInterface
{
    /**
     * @var MailerInterface;
     */
    private MailerInterface $mailer;

    /**
     * @var \Twig\Environment
     */
    private ?\Twig\Environment $twig;

    /**
     * @var string
     */
    private string $kernel_project_dir;

    /**
     * configuration parameter 'from' - from address
     *
     * Can technically be null, but MUST BE SET before sending mail!
     *
     * @var array|string|null
     */
    private string|array|null $from;

    /**
     * configuration parameter 'sender' - sender address
     *
     * @var array|string|null
     */
    private string|array|null $sender;

    /**
     * configuration parameter 'reply_to' - sender address
     *
     * @var array|string|null
     */
    private string|array|null $reply_to;

    /**
     * configuration parameter 'return_path'
     *
     * - not an array !!!
     *
     * @var string|null
     */
    private ?string $return_path;

    /**
     * configuration parameter subject_prefix - subject prefix string
     *
     * @var string|null
     */
    private ?string $subject_prefix;

    /**
     * @var int
     */
    private int $numSent;

    /**
     * Constructor
     */
    public function __construct(MailerInterface $mailer, string $kernel_project_dir, array $parameter_template, $parameter_from, $parameter_sender, $parameter_reply_to, ?string $parameter_return_path, ?string $parameter_subject_prefix, ?\Twig\Environment $twig_env)
    {
        $this->mailer = $mailer;
        $this->kernel_project_dir = $kernel_project_dir;

        $this->from = $parameter_from;
        $this->sender = $parameter_sender;
        $this->reply_to = $parameter_reply_to;
        $this->return_path = $parameter_return_path;

        $this->subject_prefix = $parameter_subject_prefix;

        $this->numSent = 0;

        // create twig environment
        if ($parameter_template['loader'] == 'filesystem') {
            $loader = new \Twig\Loader\FilesystemLoader($parameter_template['path']);
            $this->twig = new \Twig\Environment($loader, array('cache' => false, 'debug' => false, 'use_strict_variables' => false));
        } elseif ($parameter_template['loader'] == 'clone') {
            $this->twig = clone $twig_env;
        } else {
            $loader = new \Twig\Loader\ArrayLoader(array());
            $this->twig = new \Twig\Environment($loader, array('cache' => false, 'debug' => false, 'use_strict_variables' => false));
        }
    }

    /**
     * @param MailerInterface $mailer
     * @return self
     */
    public function setMailer(MailerInterface $mailer): self
    {
        $this->mailer = $mailer;

        return $this;
    }

    /**
     * @return MailerInterface
     */
    public function getMailer(): MailerInterface
    {
        return $this->mailer;
    }

    /**
     * @param array|string|null $from
     * @return self
     */
    public function setFrom($from): NotificationInterface
    {
        $this->from = $from;

        return $this;
    }

    /**
     * @param array|string|null $sender
     * @return self
     */
    public function setSender($sender): NotificationInterface
    {
        $this->sender = $sender;

        return $this;
    }

    /**
     * @param array|string|null $reply_to
     * @return self
     */
    public function setReplyTo($reply_to): NotificationInterface
    {
        $this->reply_to = $reply_to;

        return $this;
    }

    /**
     * @param string|null $return_path
     * @return self
     */
    public function setReturnPath(?string $return_path): NotificationInterface
    {
        $this->return_path = $return_path;

        return $this;
    }

    /**
     * @param string|null $prefix
     * @return self
     */
    public function setSubjectPrefix(?string $prefix): NotificationInterface
    {
        $this->subject_prefix = $prefix;

        return $this;
    }

    /**
     * Send message - based on a twig template
     *
     * @param string $template
     * @param array|string $to
     * @param string $subject
     * @param array $data
     * @param array $additional_headers
     * @param array $attachments array w. file paths
     * @throws \Exception
     */
    public function sendTemplateMessage(string $template, $to, string $subject, array $data, array $additional_headers = array(), array $attachments = array()): void
    {
        $this->sendTemplateMessageA($template, array('to' => $to), $subject, $data, $additional_headers, $attachments);
    }

    /**
     * Send message - based on a template strings (html & txt template string)
     *
     * @param array $templateStrings array( html => , txt => )
     * @param array|string $to
     * @param string $subject
     * @param array $data
     * @param array $additional_headers
     * @param array $attachments array w. file paths
     * @throws \Exception
     */
    public function sendTemplateStringMessage(array $templateStrings, $to, string $subject, array $data, array $additional_headers = array(), array $attachments = array()): void
    {
        $this->sendTemplateStringMessageA($templateStrings, array('to' => $to), $subject, $data, $additional_headers, $attachments);
    }

    /**
     * Send message - based on a twig template - with (optional) attachment
     *
     * @param string $template
     * @param array $recipients array( array|string, ... )
     * @param string $subject
     * @param array $data
     * @param array $additional_headers
     * @param array $attachments array w. file paths
     * @throws \Exception
     */
    public function sendTemplateMessageA($template, array $recipients, string $subject, array $data, array $additional_headers = array(), array $attachments = array()): void
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

        if ($this->twig->getLoader()->exists($template.'.html.twig')) {
            // render mail body
            $html = $this->twig->render(
                $template.'.html.twig',
                $data
            );
        }

        if ($this->twig->getLoader()->exists($template.'.txt.twig')) {
            // render mail body
            $plain = $this->twig->render(
                $template.'.txt.twig',
                $data
            );
        }

        if (is_null($html) && is_null($plain)) {
            throw new \Exception('NotificationService.sendTemplateMessageA: no template rendered ('.$template.')');
        }

        $this->sendMessageA($recipients, $subject, $html, $plain, $additional_headers, $attachments);
    }

    /**
     * Send message - based on a template strings - with (optional) attachment
     *
     * @param array $templateStrings array( html => , txt => )
     * @param array $recipients array( array|string, ... )
     * @param string $subject
     * @param array $data
     * @param array $additional_headers
     * @param array $attachments array w. file paths
     * @throws \Exception
     */
    public function sendTemplateStringMessageA(array $templateStrings, array $recipients, string $subject, array $data, array $additional_headers = array(), array $attachments = array()): void
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

        if (is_array($templateStrings)) {
            // array with 'html' => htmlString & 'txt' => txtString twig template strings
            if (array_key_exists('html', $templateStrings)) {
                // render mail body
                $template = $this->twig->createTemplate($templateStrings['html']);

                $html = $template->render(
                    $data
                );
            }
            if (array_key_exists('plain', $templateStrings)) {
                // render mail body
                $template = $this->twig->createTemplate($templateStrings['plain']);

                $plain = $template->render(
                    $data
                );
            }
            if (array_key_exists('txt', $templateStrings)) {
                // render mail body
                $template = $this->twig->createTemplate($templateStrings['txt']);

                $plain = $template->render(
                    $data
                );
            }
        }

        if (is_null($html) && is_null($plain)) {
            throw new \Exception('NotificationService.sendTemplateStringMessageA: no template rendered');
        }

        $this->sendMessageA($recipients, $subject, $html, $plain, $additional_headers, $attachments);
    }

    /**
     * @return int
     */
    public function sentMessagesCount(): int
    {
        return $this->numSent;
    }

    /**
     * @return self
     */
    public function resetMessagesCount(): NotificationInterface
    {
        $this->numSent = 0;

        return $this;
    }

    /**
     * @return \Twig\Environment
     */
    public function getTwig(): \Twig\Environment
    {
        return $this->twig;
    }

    /**
     * private methods
     */

    /**
     * @param $to
     * @param string $subject
     * @param string|null $html
     * @param string|null $plain
     * @return void
     * @throws \ReflectionException
     * @throws \Exception
     */
    private function sendMessage($to, string $subject, string $html = null, string $plain = null): void
    {
        if (!is_array($to) && trim($to) === '') {
            throw new \Exception('NotificationService.sendMessage: recipient address missing');
        }

        $this->sendMessageA(array('to' => $to), $subject, $html, $plain);
    }

    /**
     * @param array $recipients
     * @param string $subject
     * @param string|null $html
     * @param string|null $plain
     * @param array $additonal_headers
     * @param array $attachments
     * @return void
     * @throws \ReflectionException
     * @throws \Exception
     */
    private function sendMessageA(array $recipients, string $subject, string $html = null, string $plain = null, array $additonal_headers = array(), array $attachments = array()): void
    {
        // at least one recipient present?
        if (count($recipients) == 0) {
            throw new \Exception('NotificationService.sendMessageA: recipient address missing');
        }
        // a subject provided?
        if (trim($subject) === '') {
            throw new \Exception('NotificationService.sendMessageA: subject missing');
        }
        // content provided?
        if (is_null($html) && is_null($plain)) {
            throw new \Exception('NotificationService.sendMessageA: content missing');
        }

        // from provided? - required value !
        // sender and reply_to and return_path are optional
        if (empty($this->from)) {
            throw new \Exception('NotificationService.sendMessageA: from address missing');
        }

        $from = new Address($this->from['address'], $this->from['name']);
        if (!empty($sender)) {
            $sender = new Address($this->sender['address'], $this->sender['name']);
        }
        if (!empty($reply_to)) {
            $reply_to = new Address($this->reply_to['address'], $this->reply_to['name']);
        }
        if (!empty($return_path)) {
            $return_path = new Address($this->return_path); // not an array!
        }

        $message = (new Email())
            ->from($from) // from
            ;

        if (!empty($sender)) {
            $message
                ->sender($sender);
        }
        if (!empty($reply_to)) {
            $message
                ->replyTo($reply_to);
        }
        if (!empty($return_path)) {
            $message
                ->returnPath($return_path);
        }

        foreach ($recipients as $key => $value) {
            switch (strtolower($key)) {
                case 'to':
                    $message->to($value); // to
                    break;
                case 'cc':
                    $message->cc($value); // cc
                    break;
                case 'bcc':
                    $message->bcc($value); // bcc
                    break;
                default:
                    throw new \Exception('NotificationService.sendMessageA: invalid recipient type ('.$key.')');
            }
        }

        // headers processing - optional
        $headers = $message->getHeaders();
        foreach ($additonal_headers as $key => $value) {
            if ($headers->has($key)) {
                $headers->get($key)->setValue($value);
            } else {
                $headers->addTextHeader($key, $value);
            }
        }

        // subject (incl. prefix)
        if (empty($this->subject_prefix)) {
            $message->subject($subject);
        } else {
            $message->subject($this->subject_prefix . $subject);
        }

        /*
        // always embed logo - this needs special processing
        $file = $this->kernel_project_dir . '/../web/....png';
        $logo_src = $message->embed(\Swift_Image::fromPath($file));
        // replace logo_src placeholder in html
        $html = str_replace('%logo_src%', $logo_src, $html);
        */

        // attachment processing - optional
        foreach ($attachments as $file) {
            if (get_class($file) == "Fpdf\Fpdf") {
                // Fpdf attachment

                // get a clean filename for attachment
                $title = null;
                //$title = strtolower($file->getTitle()); does not exist

                $reflection = new \ReflectionClass($file);
                $property = $reflection->getProperty("metadata");
                $property->setAccessible(true);
                $meta = $property->getValue($file);
                if (is_array($meta) && array_key_exists('Title', $meta)) {
                    $title = $meta['Title'];
                }

                $title = $this->getAttachmentFileName($title);

                $message->attach($file->Output('', 'S'), $title.'.pdf', 'application/pdf');
            } else if (get_class($file) == "TCPDF") {
                // TCPDF attachment

                // get a clean filename for attachment
                $title = null;
                //$title = strtolower($file->getTitle()); no access

                $reflection = new \ReflectionClass($file);
                $property = $reflection->getProperty("title");
                $property->setAccessible(true);
                $title = $property->getValue($file);

                $title = $this->getAttachmentFileName($title);

<<<<<<< HEAD:Notification/Notification.php
                $message->attach(new \Swift_Attachment($file->Output('', 'S'), $title.'.pdf', 'application/pdf'));
=======
                $message->attach($file->Output('', 'S'), $title.'.pdf', 'application/pdf');
>>>>>>> Symfony-6:src/Notification/MailerNotification.php
            } else if (get_class($file) == "Swift_Attachment") {
                $message->attach($file);
            } else if (file_exists($file)) {
                if ($file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
                    $message->attachFromPath($file->getRealPath());
                } else {
                    $message->attachFromPath($file);
                }
            }
        }

        if (!is_null($html)) {
            $message->html($html);
        }
        if (!is_null($plain)) {
            $message->text($plain);
        }

        $this->mailer->send($message); // finally send the message
        $this->numSent++;
    }

    private function getAttachmentFileName(?string $title): string
    {
        if (empty($title)) {
            $title = uniqid('document_');
        }
        $title = preg_replace('/\s+/', '_', $title);
        $title = preg_replace('/[^a-z0-9\._-]/', '', $title);

        return $title;
    }
}
