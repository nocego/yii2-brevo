<?php

namespace nocego\brevo;

use Brevo\Client\Api\ContactsApi;
use Brevo\Client\Api\TransactionalSMSApi;
use Brevo\Client\Api\WhatsAppCampaignsApi;
use Brevo\Client\ApiException;
use Brevo\Client\Configuration;
use Brevo\Client\Model\AddContactToList;
use Brevo\Client\Model\CreateContact;
use Brevo\Client\Model\CreateList;
use Brevo\Client\Model\CreateWhatsAppCampaign;
use Brevo\Client\Model\CreateWhatsAppCampaignRecipients;
use Brevo\Client\Model\CreateWhatsAppTemplate;
use Brevo\Client\Model\SendTransacSms;
use Exception;
use GuzzleHttp\Client;
use simialbi\yii2\sms\BaseProvider;
use simialbi\yii2\sms\MessageInterface;
use stdClass;
use Yii;

class MessageProvider extends BaseProvider
{
    const SMS = 'sms';
    const WHATSAPP = 'whatsapp';

    /**
     * send a message
     * If the message type is SMS, the message will be sent as a transactional SMS.
     * If the message type is WhatsApp, a WhatsappTemplate will be created, approved and a WhatsAppCampaign will be created and sent.
     *
     * @param MessageInterface $message
     * @return bool
     * @throws \yii\base\Exception
     */
    protected function sendMessage(MessageInterface $message): bool
    {
        if ($message->getType() === static::SMS) {
            // check if $message->getTo() is an array
            if (!is_array($message->getTo())) {
                $message->setTo([$message->getTo()]);
            }

            // loop through all recipients
            foreach ($message->getTo() as $recipient) {
                $transactionalSMSApiInstance = $this->getTransactionalSMSApiInstance();
                $sendTransacSms = new SendTransacSms();
                $sendTransacSms->setSender($message->getFrom());
                $sendTransacSms->setRecipient($recipient);
                $sendTransacSms->setContent($message->getBody());
                $sendTransacSms->setType('transactional');

                try {
                    $transactionalSMSApiInstance->sendTransacSms($sendTransacSms);
                } catch (ApiException $e) {
                    throw new \yii\base\Exception('Error while sending SMS to ' . $recipient . ' with content ' . $message->getBody() . ' from ' . $message->getFrom() . ': ' . $e->getMessage() . '.');
                }
            }
        } elseif ($message->getType() === static::WHATSAPP) {
            try {
                $templateId = $this->createWhatsappTemplate($message);
                $this->approveWhatsappTemplate($templateId);
                $this->createWhatsappCampaign($message, $templateId);
            } catch (Exception $e) {
                throw new \yii\base\Exception('Error while sending WhatsApp to ' . $message->getTo() . ' with content ' . $message->getBody() . ' from ' . $message->getFrom() . ': ' . $e->getMessage() . '.');
            }
        }
        return true;
    }

    /**
     * create WhatsApp template
     *
     * @param MessageInterface $message
     * @return int templateId
     * @throws \yii\base\Exception
     */
    private function createWhatsappTemplate(MessageInterface $message): int
    {
        $whatsappCampaignApiInstance = $this->getWhatsappCampaignApiInstance();
        $whatsappTemplate = new CreateWhatsAppTemplate();
        $whatsappTemplate->setName('template' . time());
        $whatsappTemplate->setLanguage($message->getRegion());
        $whatsappTemplate->setCategory(CreateWhatsAppTemplate::CATEGORY_MARKETING);
        $whatsappTemplate->setBodyText($message->getBody());

        if ($whatsappTemplate->valid()) {
            try {
                $result = $whatsappCampaignApiInstance->createWhatsAppTemplate($whatsappTemplate);
                return $result->getId();
            } catch (Exception $e) {
                throw new \yii\base\Exception('Exception when calling WhatsAppCampaignsApi->createWhatsAppTemplate: ' . $e->getMessage());
            }
        } else {
            throw new \yii\base\Exception('91: Template not valid');
        }
    }

    /**
     * try to approve a WhatsApp template
     *
     * @param int $templateId
     * @return void
     * @throws \yii\base\Exception
     */
    private function approveWhatsappTemplate(int $templateId): void
    {
        $whatsappCampaignApiInstance = $this->getWhatsappCampaignApiInstance();
        try {
            $whatsappCampaignApiInstance->sendWhatsAppTemplateApproval($templateId);
            $this->waitForTemplateApproval($templateId);
            return;
        } catch (Exception $e) {
            throw new \yii\base\Exception('Exception when approving WhatsappTemplate: ' . $e->getMessage());
        }
    }

    /**
     * create a WhatsApp campaign
     *
     * @param MessageInterface $message
     * @param int $templateId
     * @throws \yii\base\Exception
     */
    private function createWhatsappCampaign(MessageInterface $message, int $templateId): void
    {
        $whatsappCampaignApiInstance = $this->getWhatsappCampaignApiInstance();
        $whatsappCampaign = new CreateWhatsAppCampaign();
        $whatsappCampaign->setName('campaign' . time());
        $whatsappCampaign->setTemplateId($templateId);
        try {
            $recipientList = $this->createWhatsappCampaignRecipientList($message->getTo());
        } catch (Exception $e) {
            throw new \yii\base\Exception('Exception when creating WhatsAppCampaignRecipientList: ' . $e->getMessage());
        }
        $whatsappCampaign->setRecipients($recipientList);
        $whatsappCampaign->setScheduledAt(gmdate('Y-m-d\TH:i:s\Z', strtotime('+1 minutes')));
        try {
            $whatsappCampaignApiInstance->createWhatsAppCampaign($whatsappCampaign);
        } catch (Exception $e) {
            throw new \yii\base\Exception('Exception when calling WhatsAppCampaignsApi->createWhatsAppCampaign: ' . $e->getMessage());
        }
    }

    /**
     * wait until the template is approved or rejected
     *
     * @param int $templateId
     * @return bool
     * @throws \yii\base\Exception
     */
    private function waitForTemplateApproval(int $templateId): bool
    {
        $whatsappCampaignApiInstance = $this->getWhatsappCampaignApiInstance();
        // get the time before 10 minutes as format (YYYY-MM-DDTHH:mm:ss.SSSZ) in UTC
        $timeBefore10Minutes = gmdate('Y-m-d\TH:i:s\Z', strtotime('-5 minutes'));
        $timeNow = gmdate('Y-m-d\TH:i:s\Z');

        try {
            $responseArray = $whatsappCampaignApiInstance->getWhatsAppTemplates($timeBefore10Minutes, $timeNow);
        } catch (Exception $e) {
            throw new \yii\base\Exception('Exception when calling WhatsAppCampaignsApi->getWhatsAppTemplates: ' . $e->getMessage());
        }
        $templates = $responseArray->getTemplates();
        // search for the templateId in the responseArray
        foreach ($templates as $template) {
            if ($template->getId() == $templateId) {
                if ($template->getStatus() === 'approved') {
                    return true;
                } elseif ($template->getStatus() === 'pending') {
                    // wait 3 seconds and try again
                    sleep(5);
                    return $this->waitForTemplateApproval($templateId);
                } else {
                    throw new \yii\base\Exception('Template was rejected: ' . $template->getStatus());
                }
            }
        }
        // template not found, wait 3 seconds and try again
        sleep(5);
        return $this->waitForTemplateApproval($templateId);
    }

    /**
     * Create a WhatsApp Campaign recipient list
     *
     * @param array|string $clientNumber
     * @return CreateWhatsAppCampaignRecipients CreateWhatsAppCampaignRecipients
     * @throws ApiException
     */
    private function createWhatsappCampaignRecipientList(array|string $clientNumber): CreateWhatsAppCampaignRecipients
    {
        $whatsappCampaignRecipients = new CreateWhatsAppCampaignRecipients();
        $whatsappCampaignRecipients->setListIds([$this->createList($clientNumber)]);

        return $whatsappCampaignRecipients;
    }

    /**
     * Create a list named by the clientNumber on a given folder
     *
     * @param array|string $clientNumber
     * @param int $folderId
     * @return int ListId
     * @throws ApiException
     */
    private function createList(array|string $clientNumber, int $folderId = 1): int
    {
        if (!is_array($clientNumber)) {
            $clientNumber = [$clientNumber];
        }

        $apiInstance = $this->getContactsApiInstance();
        $newList = new CreateList(
            [
                'name' => implode('_', $clientNumber),
                'folderId' => $folderId
            ]
        );

        $createdList = $apiInstance->createList($newList);

        // create a contact for each number in $clientNumber
        foreach ($clientNumber as $number) {
            $contactId = $this->createOrGetContact($number);
            $this->addContactToList($contactId, $createdList->getId());
        }

        return $createdList->getId();
    }

    /**
     * Create Contact or get the contactId if a contact with this number already exists
     *
     * @param string $clientNumber
     * @return int ContactId
     * @throws ApiException
     */
    private function createOrGetContact(string $clientNumber): int
    {
        $apiInstance = $this->getContactsApiInstance();

        try {
            // check if contact exists
            $result = $apiInstance->getContactInfo($clientNumber);
            $contactId = $result->getId();
        } catch (Exception $e) {
            // create contact
            $object = new stdClass();
            $object->SMS = $clientNumber;
            $object->WHATSAPP = $clientNumber;
            $contact = new CreateContact(
                [
                    'attributes' => $object
                ]
            );
            $contactId = $apiInstance->createContact($contact)->getId();
        }

        return $contactId;
    }

    /**
     * Add Contact to a list
     *
     * @param int $contactId
     * @param int $listId
     * @throws ApiException
     */
    private function addContactToList(int $contactId, int $listId): void
    {
        $apiInstance = $this->getContactsApiInstance();

        $contacts = new AddContactToList(
            [
                'ids' => [
                    $contactId
                ],
            ]
        );
        $apiInstance->addContactToList($listId, $contacts);
    }

    /**
     * Get the TransactionalSMSApi Instance
     *
     * @return TransactionalSMSApi
     */
    private function getTransactionalSMSApiInstance(): TransactionalSMSApi
    {
        return new TransactionalSMSApi(
            new Client(),
            $this->getTransactionalSMSApiConfig()
        );
    }

    /**
     * Get the WhatsappCampaignApi Instance
     *
     * @return WhatsAppCampaignsApi
     */
    private function getWhatsappCampaignApiInstance(): WhatsappCampaignsApi
    {
        return new WhatsappCampaignsApi(
            new Client(),
            $this->getApiConfig()
        );
    }

    /**
     * Get the ContactsApi Instance
     *
     * @return ContactsApi
     */
    private function getContactsApiInstance(): ContactsApi
    {
        return new ContactsApi(
            new Client(),
            $this->getApiConfig()
        );
    }

    /**
     * Get the Api Config
     *
     * @return Configuration
     */
    private function getApiConfig(): Configuration
    {
        return Configuration::getDefaultConfiguration()->setApiKey('api-key', Yii::$app->params['brevoApiKey']);
    }

    /**
     * Get the Api Config for the Transactional SMS
     *
     * @return Configuration
     */
    private function getTransactionalSMSApiConfig(): Configuration
    {
        return Configuration::getDefaultConfiguration()->setApiKey('api-key', Yii::$app->params['brevoTransactionalKey']);
    }
}
