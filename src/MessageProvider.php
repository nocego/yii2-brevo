<?php

namespace nocego\brevo;

use simialbi\yii2\sms\BaseProvider;
use simialbi\yii2\sms\MessageInterface;
use Yii;

class MessageProvider extends BaseProvider
{

    protected function sendMessage(MessageInterface $message): bool
    {
        echo Yii::$app->params['brevoApiKey'];
        echo "<br/>";
        echo "hello world";
        echo "<br/>";

        die();
        return true;
    }
}