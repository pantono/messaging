<?php

namespace Pantono\Messaging\Events\Wasender;

use Pantono\Messaging\Event\Wasender\WasenderWebhookProcess;
use Pantono\Messaging\Model\Wasender\WasenderWebhook;
use Pantono\Messaging\Model\WhatsappContact;
use Pantono\Messaging\Model\WhatsappInstance;
use Pantono\Messaging\Model\WhatsappMessage;
use Pantono\Messaging\Model\WhatsappMessageType;
use Pantono\Messaging\Whatsapp;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class WasenderMessageEvents implements EventSubscriberInterface
{
    private Whatsapp $whatsapp;

    public function __construct(Whatsapp $whatsapp)
    {
        $this->whatsapp = $whatsapp;
    }
    public static function getSubscribedEvents(): array
    {
        return [
            WasenderWebhookProcess::class => [
                ['processPrivateMessage', 0],
                ['processGroupMessage', 0],
                ['processContactUpdate', 0]
            ]
        ];
    }

    public function processPrivateMessage(WasenderWebhookProcess $event): void
    {
        $hook = $event->getWebhook();
        if ($hook->getEvent() === 'messages-personal.received') {
            $instance = $event->getInstance() ?: $this->whatsapp->getInstanceByMetaValue('apiKey', $hook->getData()['sessionId']);
            if (!$instance) {
                $instance = $this->whatsapp->getDefaultInstance();
            }
            if (!$instance) {
                return;
            }
            $type = $this->getMessageTypeFromWebhook($hook);
            if (!$type) {
                return;
            }
            $fromContact = $this->createOrUpdateContact($instance, $hook->getFromId(), $hook->getFromName());
            $message = new WhatsappMessage();
            $data = $hook->getMessageData();
            $message->setMessageId($data->get('id'));
            $message->setInstanceId($instance->getId());
            $message->setDate(\DateTimeImmutable::createFromFormat('U', $data->get('messageTimestamp')));
            $message->setType($type);
            $message->setContact($fromContact);
            $message->setIncoming(true);
            $message->setMeta($hook->getMessageObject()->all());
            $message->setStatus('received');
            $this->whatsapp->saveMessage($message);
            $event->setProcessed(true);
        }
    }

    public function processGroupMessage(WasenderWebhookProcess $event): void
    {
        $hook = $event->getWebhook();
        if ($hook->getEvent() === 'messages-group.received') {
            $instance = $event->getInstance() ?: $this->whatsapp->getInstanceByMetaValue('apiKey', $hook->getData()['sessionId']);
            if (!$instance) {
                $instance = $this->whatsapp->getDefaultInstance();
            }
            if (!$instance) {
                return;
            }
            $type = $this->getMessageTypeFromWebhook($hook);
            if (!$type) {
                return;
            }
            $fromContact = $this->createOrUpdateContact($instance, $hook->getFromId(), $hook->getFromName());
            $message = new WhatsappMessage();
            $data = $hook->getMessageData();
            $message->setMessageId($data->get('id'));
            $message->setInstanceId($instance->getId());
            $message->setDate(\DateTimeImmutable::createFromFormat('U', $data->get('messageTimestamp')));
            $message->setType($type);
            $message->setContact($fromContact);
            $message->setIncoming(true);
            $message->setMeta($hook->getMessageObject()->all());
            $message->setStatus('received');
            $this->whatsapp->saveMessage($message);
            $event->setProcessed(true);
        }
    }

    public function processContactUpdate(WasenderWebhookProcess $event): void
    {
        $hook = $event->getWebhook();
        if ($hook->getEvent() === 'contacts.updated') {
            $instance = $event->getInstance() ?: $this->whatsapp->getInstanceByMetaValue('apiKey', $hook->getData()['sessionId']);
            if (!$instance) {
                $instance = $this->whatsapp->getDefaultInstance();
            }
            if (!$instance) {
                return;
            }

            $data = $hook->getDataObject();

            if ($data->has('contacts')) {
                $contactData = $data->get('contacts');
                $id = $contactData['id'] ?? null;
                $name = $contactData['notify'] ?? null;
                if ($id && $name) {
                    $this->createOrUpdateContact($instance, $id, $name);
                }
            }
            $event->setProcessed(true);
        }
    }

    private function getMessageTypeFromWebhook(WasenderWebhook $webhook): ?WhatsappMessageType
    {
        if ($webhook->isMessageHook() === false) {
            return null;
        }
        $messageObject = $webhook->getMessageObject();
        if ($messageObject->has('conversation')) {
            return $this->whatsapp->getMessageTypeById(Whatsapp::MESSAGE_TYPE_TEXT);
        }
        if ($messageObject->has('albumMessage')) {
            return $this->whatsapp->getMessageTypeById(Whatsapp::MESSAGE_TYPE_ALBUM);
        }
        if ($messageObject->has('imageMessage')) {
            return $this->whatsapp->getMessageTypeById(Whatsapp::MESSAGE_TYPE_IMAGE);
        }
        if ($messageObject->has('pollCreationMessageV3')) {
            return $this->whatsapp->getMessageTypeById(Whatsapp::MESSAGE_TYPE_POLL);
        }
        if ($messageObject->has('contactMessage')) {
            return $this->whatsapp->getMessageTypeById(Whatsapp::MESSAGE_TYPE_CONTACT);
        }
        if ($messageObject->has('locationMessage')) {
            return $this->whatsapp->getMessageTypeById(Whatsapp::MESSAGE_TYPE_LOCATION);
        }
        if ($messageObject->has('stickerMessage')) {
            return $this->whatsapp->getMessageTypeById(Whatsapp::MESSAGE_TYPE_STICKER);
        }
        if ($messageObject->has('audioMessage')) {
            return $this->whatsapp->getMessageTypeById(Whatsapp::MESSAGE_TYPE_AUDIO);
        }
        return null;
    }

    private function createOrUpdateContact(WhatsappInstance $instance, string $id, string $name = ''): WhatsappContact
    {
        $contact = $this->whatsapp->getContactByWhatsappId($instance, $id);
        if (!$contact) {
            $contact = new WhatsappContact();
            $contact->setInstanceId($instance->getId());
            $contact->setWhatsappId($id);
            $contact->setName($name);
            $contact->setStatus('unknown');
            $contact->setOnline(false);
            $this->whatsapp->saveContact($contact);
        }
        return $contact;
    }
}
