<?php

namespace nocego\brevo;

use simialbi\yii2\sms\MessageInterface;

class Message extends \simialbi\yii2\sms\BaseMessage
{
    /**
     * @inheritDoc
     */
    public function setRegion(string $region): MessageInterface
    {
        // TODO: Implement setRegion() method.
    }

    /**
     * @inheritDoc
     */
    public function getFrom()
    {
        // TODO: Implement getFrom() method.
    }

    /**
     * @inheritDoc
     */
    public function setFrom(string $from = null): MessageInterface
    {
        // TODO: Implement setFrom() method.
    }

    /**
     * @inheritDoc
     */
    public function getTo()
    {
        // TODO: Implement getTo() method.
    }

    /**
     * @inheritDoc
     */
    public function setTo($to): MessageInterface
    {
        // TODO: Implement setTo() method.
    }

    /**
     * @inheritDoc
     */
    public function getSubject(): string
    {
        // TODO: Implement getSubject() method.
    }

    /**
     * @inheritDoc
     */
    public function setSubject(string $subject): MessageInterface
    {
        // TODO: Implement setSubject() method.
    }

    /**
     * @inheritDoc
     */
    public function setBody(string $text): MessageInterface
    {
        // TODO: Implement setBody() method.
    }

    /**
     * @inheritDoc
     */
    public function toString(): string
    {
        // TODO: Implement toString() method.
    }
}