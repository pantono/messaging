<?php

namespace Pantono\Messaging\Service;

use Pantono\Messaging\Model\WhatsappInstance;
use Pantono\Messaging\Repository\DummyWhatsappMessageRepository;

class DatabaseWhatsappInstance implements WhatsappServiceInterface
{
    private DummyWhatsappMessageRepository $repository;
    private ?WhatsappInstance $instance = null;

    public function __construct(DummyWhatsappMessageRepository $repository)
    {
        $this->repository = $repository;
    }

    public function setInstance(WhatsappInstance $instance): void
    {
        $this->instance = $instance;
    }

    public function deleteMessage(string $messageId): array
    {
        $this->logCall('deleteMessage', ['messageId' => $messageId]);
        return ['success' => true];
    }

    public function sendText(string $to, string $message, ?string $replyTo = null): array
    {
        $this->logCall('sendText', ['to' => $to, 'message' => $message]);
        return ['msgId' => 1, 'jid' => '+777777', 'status' => 'in_progress'];
    }

    public function sendImage(string $to, string $imageUrl, ?string $text = null, ?string $replyTo = null): array
    {
        $this->logCall('sendImage', ['to' => $to, 'imageUrl' => $imageUrl]);
        return ['msgId' => 1, 'jid' => '+777777', 'status' => 'in_progress'];
    }

    public function sendVideo(string $to, string $videoUrl, ?string $text = null, ?string $replyTo = null): array
    {
        $this->logCall('sendVideo', ['to' => $to, 'videoUrl' => $videoUrl, 'text' => $text, $replyTo]);
        return ['msgId' => 1, 'jid' => '+777777', 'status' => 'in_progress'];
    }

    public function sendAudio(string $to, string $audioUrl, ?string $text = null, ?string $replyTo = null): array
    {
        $this->logCall('sendAudio', ['to' => $to, 'audioUrl' => $audioUrl, 'text' => $text, 'reply_to' => $replyTo]);
        return ['msgId' => 1, 'jid' => '+777777', 'status' => 'in_progress'];
    }

    public function sendSticker(string $to, string $stickerUrl, ?string $text = null, ?string $replyTo = null): array
    {
        $this->logCall('sendSticker', ['to' => $to, 'stickerUrl' => $stickerUrl, 'text' => $text, 'replyTo' => $replyTo]);
        return ['msgId' => 1, 'jid' => '+777777', 'status' => 'in_progress'];
    }

    public function sendDocument(string $to, string $documentUrl, ?string $fileName = null, ?string $text = null, ?string $replyTo = null): array
    {
        $this->logCall('sendDocument', ['to' => $to, 'documentUrl' => $documentUrl, 'fileName' => $fileName, 'text' => $text, 'replyTo' => $replyTo]);
        return ['msgId' => 1, 'jid' => '+777777', 'status' => 'in_progress'];
    }

    public function sendContact(string $to, array $contact, ?string $replyTo = null): array
    {
        $this->logCall('sendContact', ['to' => $to, 'contact' => $contact, 'replyTo' => $replyTo]);
        return ['msgId' => 1, 'jid' => '+777777', 'status' => 'in_progress'];
    }

    public function sendLocation(string $to, float $latitude, float $longitude, ?string $name = null, ?string $address = null, ?string $replyTo = null): array
    {
        $this->logCall('sendLocation', ['to' => $to, 'latitude' => $latitude, 'longitude' => $longitude, 'name' => $name, 'address' => $address, 'replyTo' => $replyTo]);
        return ['msgId' => 1, 'jid' => '+777777', 'status' => 'in_progress'];
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function listGroups(): array
    {
        $this->logCall('listGroups', []);
        return [['jid' => '1111@g.us', 'name' => 'Test']];
    }

    /**
     * @return array<string,mixed>
     */
    public function createGroup(string $subject, array $participants): array
    {
        $this->logCall('createGroup', ['subject' => $subject, 'participants' => $participants]);
        return ['id' => '1111@g.us', 'name' => 'Test', 'owner' => '1111@s.whatsapp.net'];
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    public function addGroupParticipants(string $groupId, array $participants): array
    {
        $this->logCall('addGroupParticipants', ['groupId' => $groupId, 'participants' => $participants]);
        return [['jid' => '1111@g.us', 'staus' => 200, 'message' => 'added']];
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    public function removeGroupParticipants(string $groupId, array $participants): array
    {
        $this->logCall('removeGroupParticipants', ['groupId' => $groupId, 'participants' => $participants]);
        return [['jid' => '1111@g.us', 'staus' => 200, 'message' => 'removed']];
    }

    public function getContact(string $phone): array
    {
        $this->logCall('getContact', ['phone' => $phone]);
        return ['id' => '111@s.whatsapp.net'];
    }

    public function blockContact(string $phone): array
    {
        $this->logCall('blockContact', ['phone' => $phone]);
        return ['message' => '"Contact blocked'];
    }

    public function unblockContact(string $phone): array
    {
        $this->logCall('unblockContact', ['phone' => $phone]);
        return ['message' => '"Contact unblocked'];
    }

    private function logCall(string $call, array $data): void
    {
        $this->repository->logCall($this->instance->getId(), $call, $data);
    }
}
