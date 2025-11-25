<?php

namespace Pantono\Messaging\Events\Wasender;

use Pantono\Messaging\Event\Wasender\WasenderWebhookProcess;
use Pantono\Messaging\Model\Wasender\WasenderWebhook;
use Pantono\Messaging\Model\WhatsappContact;
use Pantono\Messaging\Model\WhatsappGroup;
use Pantono\Messaging\Model\WhatsappInstance;
use Pantono\Messaging\Model\WhatsappMessage;
use Pantono\Messaging\Model\WhatsappMessageType;
use Pantono\Messaging\Utility\Wasender\DecryptWasenderMediaFile;
use Pantono\Messaging\Whatsapp;
use Pantono\Queue\QueueManager;
use Pantono\Storage\FileStorage;
use Pantono\Storage\Model\StoredFile;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class WasenderMessageEvents implements EventSubscriberInterface
{
    private Whatsapp $whatsapp;
    private FileStorage $fileStorage;
    private QueueManager $queueManager;

    public function __construct(Whatsapp $whatsapp, FileStorage $fileStorage, QueueManager $queueManager)
    {
        $this->whatsapp = $whatsapp;
        $this->fileStorage = $fileStorage;
        $this->queueManager = $queueManager;
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
        if ($event->getWebhook()->getEvent() === 'chats.upsert') {
            $instance = $this->getInstanceFromHook($event);
            $groupData = $event->getWebhook()->getDataObject()->get('chats', []);
            if (is_array($groupData)) {
                foreach ($groupData as $group) {
                    $groupId = $group['id'] ?? null;
                    $groupModel = $this->whatsapp->getGroupByWhatsappId($instance, $groupId);
                    if (!$groupModel) {
                        $groupModel = new WhatsappGroup();
                        $groupModel->setSubject($group['name']);
                        $groupModel->setGroupId($groupId);
                        $groupModel->setInstanceId($instance->getId());
                        $this->whatsapp->saveGroup($groupModel);
                        $this->queueManager->createTask('wasender_update_group', ['id' => $groupId, 'instance_id' => $instance->getId()]);
                    }
                }
            }
        }
    }

    public function processIncomingMessage(WasenderWebhookProcess $event): void
    {
        $hook = $event->getWebhook();
        if ($hook->getEvent() === 'messages.received' || $hook->getEvent() === 'messages-personal.received' || $hook->getEvent() === 'messages-group.received') {
            $id = $hook->getMessageData()->get('id');
            $this->whatsapp->acquireMessageLock($id);
            $instance = $this->getInstanceFromHook($event);
            $message = $this->createMessageFromWebhook($instance, $hook);
            if ($message) {
                try {
                    $this->whatsapp->saveMessage($message);
                } catch (\Exception $e) {
                    if (str_contains($e->getMessage(), 'Deadlock')) {
                        sleep(1);
                        $this->whatsapp->saveMessage($message);
                    } else {
                        throw $e;
                    }
                }
                $event->setProcessed(true);
            }
            $this->whatsapp->releaseMessageLock($id);
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
                    $this->whatsapp->createOrUpdateContact($instance, $id, $name);
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
        if ($messageObject->has('reactionMessage')) {
            return $this->whatsapp->getMessageTypeById(Whatsapp::MESSAGE_TYPE_REACTION);
        }
        if ($messageObject->has('videoMessage')) {
            return $this->whatsapp->getMessageTypeById(Whatsapp::MESSAGE_TYPE_VIDEO);
        }
        return null;
    }

    private function createMessageFromWebhook(WhatsappInstance $instance, WasenderWebhook $hook, int $attempt = 1): ?WhatsappMessage
    {
        $type = $this->getMessageTypeFromWebhook($hook);
        if (!$type) {
            return null;
        }
        $fromContact = $this->whatsapp->createOrUpdateContact($instance, $hook->getFromId(), $hook->getFromName());
        $data = $hook->getMessageObject();
        $containerData = $hook->getMessageData();
        $this->whatsapp->startTransaction();
        try {
            $message = $this->whatsapp->getMessageByWhatsappId($instance->getId(), $containerData->get('id'));
            if ($message === null) {
                $message = new WhatsappMessage();
            }
            $message->setMessageId($containerData->get('id'));
            $message->setInstanceId($instance->getId());
            if ($containerData->has('messageTimestamp')) {
                $ts = $containerData->get('messageTimestamp');
                if (is_array($containerData->get('messageTimestamp'))) {
                    $ts = $containerData->get('messageTimestamp')['low'];
                }
                $message->setDate(\DateTimeImmutable::createFromFormat('U', $ts));
            } else {
                $message->setDate(new \DateTimeImmutable());
            }
            $keyParams = new ParameterBag($containerData->get('key', []));

            $message->setIncoming(true);
            if ($keyParams->has('fromMe')) {
                if ($keyParams->get('fromMe') === true) {
                    $message->setIncoming(false);
                }
            }
            $message->setType($type);
            $message->setContact($fromContact);
            $message->setMeta($hook->getMessageObject()->all());
            $message->setStatus('received');
            if ($hook->getGroupId()) {
                $group = $this->whatsapp->getGroupByWhatsappId($instance, $hook->getGroupId());
                if ($group === null) {
                    $group = new WhatsappGroup();
                    $group->setInstanceId($instance->getId());
                    $group->setGroupId($hook->getGroupId());
                    $this->whatsapp->saveGroup($group);
                }
                $message->setGroup($group);
            }
            $parentId = $hook->getParentId();
            if ($parentId && $parentId !== $message->getMessageId()) {
                $parentMessage = $this->whatsapp->getMessageByWhatsappId($instance->getId(), $parentId);
                if ($parentMessage === null) {
                    sleep(1);
                    if ($attempt > 5) {
                        throw new \RuntimeException('Could not find parent message: ' . $parentId);
                    }
                    return $this->createMessageFromWebhook($instance, $hook, $attempt + 1);
                }
                $message->setParentId($parentId);
                $message->setParentMessage($parentMessage);
            }
            if ($message->getType()->getId() === Whatsapp::MESSAGE_TYPE_TEXT) {
                if ($data->has('extendedTextMessage')) {
                    $replyContext = new ParameterBag($data->get('extendedTextMessage', []));
                    $text = $replyContext->get('text');
                    $context = $replyContext->get('contextInfo', []);
                    $replyTo = $context['stanzaId'] ?? null;
                    if ($replyTo) {
                        $replyMessage = $this->getReplyToMessageWait($instance->getId(), $replyTo);
                        if ($replyMessage) {
                            $message->setReplyToMessage($replyMessage);
                            $message->setReplyTo($replyTo);
                        }
                    }
                } else {
                    $text = $data->get('conversation', '');
                }
                $message->setTextContent($text);
            } elseif ($message->getType()->getId() === Whatsapp::MESSAGE_TYPE_REACTION) {
                if ($data->has('reactionMessage')) {
                    $reaction = new ParameterBag($data->get('reactionMessage', []));
                    $message->setTextContent($reaction->get('text'));
                    $reactionKey = new ParameterBag($reaction->get('key', []));
                    if ($reactionKey->has('id')) {
                        $replyToId = $reactionKey->get('id');
                        $replyToMessage = $this->getReplyToMessageWait($instance->getId(), $replyToId);
                        if ($replyToMessage) {
                            $message->setReplyTo($replyToId);
                            $message->setReplyToMessage($replyToMessage);
                        }
                    }
                }
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

            $this->whatsapp->endTransaction();
            return $message;
        } catch (\Exception $e) {
            $this->whatsapp->endTransaction();
            throw $e;
        }
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

    private function getReplyToMessageWait(int $instanceId, string $replyToId, int $attempt = 0): ?WhatsappMessage
    {
        $replyToMessage = $this->whatsapp->getMessageByWhatsappId($instanceId, $replyToId);
        if ($replyToMessage === null) {
            if ($attempt > 5) {
                return null;
            }
            sleep(1);
            return $this->getReplyToMessageWait($instanceId, $replyToId, $attempt + 1);
        }
        return $replyToMessage;
    }
}
