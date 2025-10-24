<?php

namespace Pantono\Messaging\Service;

use Pantono\Messaging\Model\WhatsappInstance;

interface WhatsappServiceInterface
{
    public function setInstance(WhatsappInstance $instance): void;

    /**
     * @return array<string,mixed>
     */
    public function deleteMessage(string $messageId): array;
    /**
     * @return array<string,mixed>
     */
    public function sendText(string $to, string $message, ?string $replyTo = null): array;

    /**
     * @return array<string,mixed>
     */
    public function sendImage(string $to, string $imageUrl, ?string $text = null, ?string $replyTo = null): array;

    /**
     * @return array<string,mixed>
     */
    public function sendVideo(string $to, string $videoUrl, ?string $text = null, ?string $replyTo = null): array;

    /**
     * @return array<string,mixed>
     */
    public function sendAudio(string $to, string $audioUrl, ?string $text = null, ?string $replyTo = null): array;

    /**
     * @return array<string,mixed>
     */
    public function sendSticker(string $to, string $stickerUrl, ?string $text = null, ?string $replyTo = null): array;

    /**
     * @return array<string,mixed>
     */
    public function sendDocument(string $to, string $documentUrl, ?string $fileName = null, ?string $text = null, ?string $replyTo = null): array;

    /**
     * @return array<string,mixed>
     */
    public function sendContact(string $to, array $contact, ?string $replyTo = null): array;

    /**
     * @return array<string,mixed>
     */
    public function sendLocation(string $to, float $latitude, float $longitude, ?string $name = null, ?string $address = null, ?string $replyTo = null): array;

    /**
     * @return array<string,mixed>
     */
    public function listGroups(): array;
    /**
     * @return array<string,mixed>
     */
    public function createGroup(string $subject, array $participants): array;
    /**
     * @return array<string,mixed>
     */
    public function addGroupParticipants(string $groupId, array $participants): array;
    /**
     * @return array<string,mixed>
     */
    public function removeGroupParticipants(string $groupId, array $participants): array;
    /**
     * @return array<string,mixed>
     */
    public function getContact(string $phone): array;
    /**
     * @return array<string,mixed>
     */
    public function blockContact(string $phone): array;
    /**
     * @return array<string,mixed>
     */
    public function unblockContact(string $phone): array;
}
