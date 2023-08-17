<?php

namespace nocego\brevo;

use simialbi\yii2\sms\BaseMessage;

class Message extends BaseMessage
{
    /**
     * @var string Region (language) of the message
     */
    private string $_region = 'de';

    /**
     * @var string|null Sender of the message
     * String with a maximum length of 11 characters
     */
    private ?string $_from = null;

    /**
     * @var string|array|null Recipient of the message
     * String with the format +xxxxxxxxxxx
     */
    private string|array|null $_to = null;

    /**
     * @var string|null Content of the message
     */
    private ?string $_body = null;

    /**
     * @var string Type of the message
     * Possible values: sms, whatsapp
     */
    private string $_type = MessageProvider::SMS;

    /**
     * @inheritDoc
     */
    public function getRegion(): string
    {
        return $this->_region;
    }

    /**
     * @inheritDoc
     */
    public function getFrom(): string|null
    {
        return $this->_from;
    }

    /**
     * @inheritDoc
     */
    public function getTo(): array|string|null
    {
        return $this->_to;
    }

    /**
     * @inheritDoc
     */
    public function getSubject(): string
    {
        return "";
    }

    /**
     * get the body of the message
     * @return string|null
     */
    public function getBody(): string|null
    {
        return $this->_body;
    }

    /**
     * get the type of the message
     * @return string
     */
    public function getType(): string
    {
        return $this->_type;
    }

    /**
     * @inheritDoc
     */
    public function setRegion(string $region): self
    {
        $this->_region = $region;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setFrom(string $from = null): self
    {
        $this->_from = $from;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setTo($to): self
    {
        $this->_to = $to;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setSubject(string $subject): self
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setBody(string $text): self
    {
        $this->_body = $text;

        return $this;
    }

    /**
     * set the type of the message
     * @param string $type
     * @return self
     */
    public function setType(string $type): self
    {
        $this->_type = $type;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function toString(): string
    {
        return $this->getBody();
    }
}
