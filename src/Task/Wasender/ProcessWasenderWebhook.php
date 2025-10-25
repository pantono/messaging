<?php

namespace Pantono\Messaging\Task\Wasender;

use Pantono\Messaging\Event\Wasender\WasenderWebhookProcess;
use Pantono\Messaging\Model\Wasender\WasenderWebhook;
use Pantono\Messaging\Model\WhatsappContact;
use Pantono\Messaging\Model\WhatsappGroup;
use Pantono\Messaging\Model\WhatsappGroupMember;
use Pantono\Messaging\Model\WhatsappMessage;
use Pantono\Messaging\Model\WhatsappMessageType;
use Pantono\Messaging\Service\WasenderService;
use Pantono\Messaging\Whatsapp;
use Pantono\Queue\Task\AbstractTask;
use Pantono\Storage\FileStorage;
use Pantono\Storage\Model\StoredFile;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\ParameterBag;

class ProcessWasenderWebhook extends AbstractTask
{
    private Whatsapp $whatsapp;
    private WasenderService $service;
    private FileStorage $fileStorage;
    private EventDispatcher $dispatcher;

    public function __construct(Whatsapp $whatsapp, WasenderService $service, FileStorage $storage, EventDispatcher $dispatcher)
    {
        $this->whatsapp = $whatsapp;
        $this->service = $service;
        $this->fileStorage = $storage;
        $this->dispatcher = $dispatcher;
    }

    public function process(ParameterBag $parameters): array
    {
        $id = $parameters->get('id');
        $webhook = $this->service->getWebhookById($id);
        if (!$webhook) {
            return ['success' => false, 'error' => 'Webhook not found'];
        }
        $dataParams = new ParameterBag($webhook->getData());
        $sessionId = $dataParams->get('sessionId');
        if (!$sessionId) {
            return ['success' => false, 'error' => 'No session id'];
        }
        $instance = $this->whatsapp->getInstanceByMetaValue('apiKey', $sessionId);
        if (!$instance) {
            $all = $this->whatsapp->getAllInstances();
            if (count($all) === 1) {
                $instance = $all[0];
            }
        }

        if ($instance === null) {
            return ['success' => false, 'error' => 'Instance for API key not found'];
        }

        $event = new WasenderWebhookProcess();
        $event->setWebhook($webhook);
        $event->setInstance($instance);
        $this->dispatcher->dispatch($event);
        if ($event->isProcessed()) {
            $webhook->setProcessed(true);
            $this->service->saveWebhook($webhook);
        }

        return ['success' => true, 'processed' => $event->isProcessed()];

        if ($webhook->getEvent() === 'messages-group.received') {
            $messageParams = new ParameterBag($webhook->getData()['data']['messages'] ?? []);
            $message = new WhatsappMessage();
            $message->setType($this->getMessageTypeFromWebhook($webhook));
            $message->setMessageId($messageParams->get('id'));
            $message->setDate(\DateTimeImmutable::createFromFormat('U', $messageParams->get('timestamp')));
            $messageObject = new ParameterBag($messageParams->get('message') ?? []);
            $message->setIncoming(true);
            $contact = $this->whatsapp->getContactByWhatsappId($instance, $messageParams->get('from'));
            if ($message->getType()->getId() === Whatsapp::MESSAGE_TYPE_TEXT) {
                $message->setTextContent($messageObject->get('conversation'));
            } elseif ($message->getType()->getId() === Whatsapp::MESSAGE_TYPE_IMAGE) {
                $imageMessage = $messageObject->get('imageMessage');
                $message->setTextContent($imageMessage['caption']);
                $file = $this->storeMediaContent($webhook->getData());
                if ($file) {
                    $message->setFile($file);
                }
            }
        }

        if ($webhook->getEvent() === 'messages-personal.received') {
        }

        if ($webhook->getEvent() === 'contacts.update') {
            $data = new ParameterBag($webhook->getData()['data'] ?? []);
            $contactData = $data->get('contacts');
            if ($contactData['id']) {
                $contact = $this->whatsapp->getContactByWhatsappId($instance, $contactData['id']);
                if (!$contact) {
                    $contact = new WhatsappContact();
                    $contact->setInstanceId($instance->getId());
                    $contact->setWhatsappId($contactData['id']);
                    $contact->setName($contactData['notify']);
                    $contact->setStatus('unknown');
                    $contact->setOnline(false);
                    $this->whatsapp->saveContact($contact);
                    return ['success' => true, 'result' => 'Contact created'];
                }

                if ($contact->getName() !== $contactData['notify']) {
                    $contact->setName($contactData['notify']);
                    $this->whatsapp->saveContact($contact);
                    return ['success' => true, 'result' => 'Contact updated'];
                }
            }
        }

        if ($webhook->getEvent() === 'groups.upsert') {
            $groupResponse = $this->service->getAllGroups();
            if ($groupResponse['success'] === true) {
                $data = $groupResponse['data'];
                foreach ($groupResponse['data'] as $group) {
                    $groupId = $group['id'] ?? null;
                    if (!$groupId) {
                        continue;
                    }
                    $meta = $this->service->getGroupMetadata($groupId);
                    if ($meta['success'] !== true) {
                        continue;
                    }
                    $groupMeta = $meta['data'];
                    $group = $this->whatsapp->getGroupByWhatsappId($instance, $groupId);
                    if (!$group) {
                        $group = new WhatsappGroup();
                        $group->setInstanceId($instance->getId());
                        $group->setGroupId($groupId);
                    }
                    $group->setSubject($groupMeta['subject']);
                    $group->setOwnerId($groupMeta['ownerJid']);
                    foreach ($groupMeta['participants'] as $participant) {
                        $member = new WhatsappGroupMember();
                        if ($group->hasMember($participant['jid']) === false) {
                            $contact = $this->whatsapp->getContactByWhatsappId($instance, $participant['jid']);
                            if (!$contact) {
                            }
                        }
                    }
                }
            }
        }
        return ['success' => false, 'error' => 'Unknown event'];
    }

    private function getMessageTypeFromWebhook(WasenderWebhook $webhook): ?WhatsappMessageType
    {
        $data = $webhook->getData();
        $messageData = $data['data']['messages']['message'] ?: [];
        if (isset($messageData['conversation'])) {
            return $this->whatsapp->getMessageTypeById(Whatsapp::MESSAGE_TYPE_TEXT);
        }
        if (isset($messageData['albumMessage'])) {
            return $this->whatsapp->getMessageTypeById(Whatsapp::MESSAGE_TYPE_ALBUM);
        }
        if (isset($messageData['imageMessage'])) {
            return $this->whatsapp->getMessageTypeById(Whatsapp::MESSAGE_TYPE_IMAGE);
        }
        if (isset($messageData['pollCreationMessageV3'])) {
            return $this->whatsapp->getMessageTypeById(Whatsapp::MESSAGE_TYPE_POLL);
        }
        if (isset($messageData['contactMessage'])) {
            return $this->whatsapp->getMessageTypeById(Whatsapp::MESSAGE_TYPE_CONTACT);
        }
        if (isset($messageData['locationMessage'])) {
            return $this->whatsapp->getMessageTypeById(Whatsapp::MESSAGE_TYPE_LOCATION);
        }
        if (isset($messageData['stickerMessage'])) {
            return $this->whatsapp->getMessageTypeById(Whatsapp::MESSAGE_TYPE_STICKER);
        }
        if (isset($messageData['audioMessage'])) {
            return $this->whatsapp->getMessageTypeById(Whatsapp::MESSAGE_TYPE_AUDIO);
        }
        return null;
    }

    private function downloadFile(string $url): string
    {
        $context = stream_context_create([
            "http" => [
                "follow_location" => true,
            ]
        ]);
        return file_get_contents($url, false, $context);
    }

    private function getDecryptionKeys(string $mediaKey, string $type = 'image', int $length = 112): string
    {
        $info = match ($type) {
            'image' => 'WhatsApp Image Keys',
            'video' => 'WhatsApp Video Keys',
            'audio' => 'WhatsApp Audio Keys',
            'document' => 'WhatsApp Document Keys',
            default => throw new \Exception("Invalid media type"),
        };

        return hash_hkdf('sha256', base64_decode($mediaKey), $length, $info, '');
    }

    private function decryptWhatsAppMedia(string $mediaKey, string $url, string $outputPath, string $mediaType = 'image'): string
    {
        // Download the encrypted file
        $encFile = $this->downloadFile($url);
        if (!$encFile) {
            throw new \Exception("Failed to download file");
        }

        // Get decryption keys based on media type
        $keys = $this->getDecryptionKeys($mediaKey, $mediaType);
        $iv = substr($keys, 0, 16);
        $cipherKey = substr($keys, 16, 32);

        // Remove the last 10 bytes (MAC) from the encrypted file
        $ciphertext = substr($encFile, 0, strlen($encFile) - 10);

        // Decrypt the file using AES-256-CBC
        $plaintext = openssl_decrypt($ciphertext, 'aes-256-cbc', $cipherKey, OPENSSL_RAW_DATA, $iv);
        if (!$plaintext) {
            throw new \Exception("Failed to decrypt media");
        }
        return $plaintext;
    }

    private function storeMediaContent(array $webhookData): ?StoredFile
    {
        // Extract message data
        $message = $webhookData['data']['messages']['message'] ?? null;

        if (!$message) {
            return null;
        }

        $filename = 'Whatsapp-File-' . (new \DateTimeImmutable())->format('Y-m-d');
        // Determine the message type and extract media information
        if (isset($message['imageMessage'])) {
            $mediaInfo = $message['imageMessage'];
            $mediaType = 'image';
            $filename = 'Whatsapp-Image-' . (new \DateTimeImmutable())->format('Y-m-d');
            $extension = '.jpg';
        } elseif (isset($message['videoMessage'])) {
            $mediaInfo = $message['videoMessage'];
            $mediaType = 'video';
            $extension = '.mp4';
            $filename = 'Whatsapp-Video-' . (new \DateTimeImmutable())->format('Y-m-d');
        } elseif (isset($message['audioMessage'])) {
            $mediaInfo = $message['audioMessage'];
            $mediaType = 'audio';
            $extension = '.ogg';
            $filename = 'Whatsapp-Audio-' . (new \DateTimeImmutable())->format('Y-m-d');
        } elseif (isset($message['documentMessage'])) {
            $mediaInfo = $message['documentMessage'];
            $mediaType = 'document';
            $extension = ''; // Use the original extension if available
        } else {
            // Not a media message
            return null;
        }

        // Extract required information
        $mediaKey = $mediaInfo['mediaKey'] ?? null;
        $url = $mediaInfo['url'] ?? null;
        $messageId = $webhookData['data']['key']['id'] ?? 'unknown';

        if (!$mediaKey || !$url) {
            return null;
        }

        // Create a unique filename
        $outputPath = __DIR__ . '/media/' . $messageId . $extension;

        // Ensure the media directory exists
        if (!is_dir(__DIR__ . '/media/')) {
            mkdir(__DIR__ . '/media/', 0755, true);
        }

        try {
            // Decrypt and save the media file
            $contents = $this->decryptWhatsAppMedia($mediaKey, $url, $outputPath, $mediaType);
            return $this->fileStorage->uploadFile($filename . '.' . $extension, $contents);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getFrom()
    {
    }
}
