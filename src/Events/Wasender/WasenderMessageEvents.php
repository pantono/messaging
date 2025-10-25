<?php

namespace Pantono\Messaging\Events\Wasender;

use Pantono\Messaging\Event\Wasender\WasenderWebhookProcess;
use Pantono\Messaging\Model\Wasender\WasenderWebhook;
use Pantono\Messaging\Model\WhatsappContact;
use Pantono\Messaging\Model\WhatsappInstance;
use Pantono\Messaging\Model\WhatsappMessage;
use Pantono\Messaging\Model\WhatsappMessageType;
use Pantono\Messaging\Service\WasenderService;
use Pantono\Messaging\Utility\Wasender\DecryptWasenderMediaFile;
use Pantono\Messaging\Whatsapp;
use Pantono\Storage\FileStorage;
use Pantono\Storage\Model\StoredFile;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class WasenderMessageEvents implements EventSubscriberInterface
{
    private Whatsapp $whatsapp;
    private FileStorage $fileStorage;
    private WasenderService $service;

    public function __construct(Whatsapp $whatsapp, FileStorage $fileStorage, WasenderService $service)
    {
        $this->whatsapp = $whatsapp;
        $this->fileStorage = $fileStorage;
        $this->service = $service;
    }
    public static function getSubscribedEvents(): array
    {
        return [
            WasenderWebhookProcess::class => [
                ['processIncomingMessage', 0],
                ['processContactUpdate', 0],
                ['processGroupUpdate', 0]
            ]
        ];
    }

    public function processGroupUpdate(WasenderWebhookProcess $event): void
    {
        if ($event->getWebhook()->getEvent() === 'groups.upsert') {
            $instance = $this->getInstanceFromHook($event);
            foreach ($this->service->getAllGroups() as $group) {
                $groupData = $this->service->getGroupMetadata($group['id']);
                if ($groupData['success'] === true) {
                }
                $groupData = $this->whatsapp->getGroupByWhatsappId($instance, $group['id']);
            }
        }
    }

    public function processIncomingMessage(WasenderWebhookProcess $event): void
    {
        $hook = $event->getWebhook();
        if ($hook->getEvent() === 'messages-personal.received' || $hook->getEvent() === 'messages-group.received') {
            $instance = $this->getInstanceFromHook($event);
            $message = $this->createMessageFromWebhook($instance, $hook);
            if ($message) {
                $this->whatsapp->saveMessage($message);
                $event->setProcessed(true);
            }
        }
    }

    public function processContactUpdate(WasenderWebhookProcess $event): void
    {
        $hook = $event->getWebhook();
        if ($hook->getEvent() === 'contacts.update') {
            $instance = $this->getInstanceFromHook($event);

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
        if ($messageObject->has('extendedTextMessage')) {
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

    private function createMessageFromWebhook(WhatsappInstance $instance, WasenderWebhook $hook): ?WhatsappMessage
    {
        $type = $this->getMessageTypeFromWebhook($hook);
        if (!$type) {
            return null;
        }
        $fromContact = $this->createOrUpdateContact($instance, $hook->getFromId(), $hook->getFromName());
        $data = $hook->getMessageObject();
        $containerData = $hook->getMessageData();
        $message = $this->whatsapp->getMessageByWhatsappId($instance->getId(), $containerData->get('id'));
        if ($message === null) {
            $message = new WhatsappMessage();
        }
        $message->setMessageId($containerData->get('id'));
        $message->setInstanceId($instance->getId());
        $message->setDate(\DateTimeImmutable::createFromFormat('U', $containerData->get('messageTimestamp')));
        $message->setType($type);
        $message->setContact($fromContact);
        $message->setIncoming(true);
        $message->setMeta($hook->getMessageObject()->all());
        $message->setStatus('received');
        $messageObject = null;
        if ($message->getType()->getId() === Whatsapp::MESSAGE_TYPE_TEXT) {
            if ($data->has('extendedTextMessage')) {
                $replyContext = new ParameterBag($data->get('extendedTextMessage', []));
                $text = $replyContext->get('text');
                $context = $replyContext->get('contextInfo', []);
                $replyTo = $context['stanzaId'] ?? null;
                $replyMessage = $this->whatsapp->getMessageByWhatsappId($instance->getId(), $replyTo);
                if ($replyTo) {
                    $message->setReplyToMessage($replyMessage);
                    $message->setReplyTo($replyTo);
                }
            } else {
                $text = $data->get('conversation', '');
            }
            $message->setTextContent($text);
        } elseif ($message->getType()->getId() === Whatsapp::MESSAGE_TYPE_IMAGE) {
            $messageObject = new ParameterBag($data->get('imageMessage', []));
            if ($messageObject->has('caption')) {
                $message->setTextContent($messageObject->get('caption'));
            } else {
                $message->setTextContent('');
            }
            $file = $this->getFileFromMessageObject($data);
            if ($file) {
                $message->setFile($file);
            }
        } elseif ($message->getType()->getId() === Whatsapp::MESSAGE_TYPE_STICKER) {
            $messageObject = new ParameterBag($data->get('stickerMessage', []));
            if ($messageObject->has('caption')) {
                $message->setTextContent($messageObject->get('caption'));
            } else {
                $message->setTextContent('');
            }
            $file = $this->getFileFromMessageObject($data);
            if ($file) {
                $message->setFile($file);
            }
        } elseif ($message->getType()->getId() === Whatsapp::MESSAGE_TYPE_AUDIO) {
            $messageObject = new ParameterBag($data->get('audioMessage', []));
            if ($messageObject->has('caption')) {
                $message->setTextContent($messageObject->get('caption'));
            } else {
                $message->setTextContent('');
            }
            $file = $this->getFileFromMessageObject($data);
            if ($file) {
                $message->setFile($file);
            }
        }

        return $message;
    }

    private function getFileFromMessageObject(ParameterBag $messageObject): ?StoredFile
    {
        $file = DecryptWasenderMediaFile::decryptFileFromMessageObject($messageObject);
        if ($file) {
            return $this->fileStorage->uploadFile($file['filename'], $file['contents']);
        }
        return null;
    }

    private function getInstanceFromHook(WasenderWebhookProcess $event): WhatsappInstance
    {
        $instance = $event->getInstance() ?: $this->whatsapp->getInstanceByMetaValue('apiKey', $event->getWebhook()->getData()['sessionId']);
        if (!$instance) {
            $instance = $this->whatsapp->getDefaultInstance();
        }

        if (!$instance) {
            throw new \RuntimeException('No instance available from api key or default settings');
        }
        return $instance;
    }
}
