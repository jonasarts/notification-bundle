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

/**
 * NotificationInterface.
 *
 * Interface to the notification service
 */
interface NotificationInterface
{
    public function setFrom($from): self;
    public function setSender($sender): self;
    public function setReplyTo($reply_to): self;
    public function setReturnPath(string $return_path): self;
    public function setSubjectPrefix(string $prefix): self;

    public function sendTemplateMessage(string $template, $to, string $subject, array $data, array $additonal_headers = array(), array $attachments = array());
    public function sendTemplateStringMessage(array $templateStrings, $to, string $subject, array $data, array $additonal_headers = array(), array $attachments = array());
    public function sendTemplateMessageA($template, array $recipients, string $subject, array $data, array $additonal_headers = array(), array $attachments = array());
    public function sendTemplateStringMessageA(array $templateStrings, array $recipients, string $subject, array $data, array $additonal_headers = array(), array $attachments = array());

    public function sentMessagesCount(): int;
    public function resetMessagesCount(): self;

    public function getTwig(): \Twig\Environment;
}
