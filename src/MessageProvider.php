<?php

namespace yii2\brevo;

use simialbi\yii2\sms\BaseProvider;
use simialbi\yii2\sms\MessageInterface;

class MessageProvider extends BaseProvider
{

    protected function sendMessage(MessageInterface $message): bool
    {
        echo "hello world";
        die();
        return true;
    }
}