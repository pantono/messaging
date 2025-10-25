<?php

namespace Pantono\Messaging\Model\Wasender;

class WasenderImageMessage implements WasenderMessageInterface
{
    private string $id;
    private string $fromName;
    private string $fromId;
    private ?string $groupId = null;
    private string $imageUrl;
    private \DateTimeInterface $date;
    private ?string $caption = null;

    private ?string $encryptedImageData = null;
    /**
     * @var array<string,mixed>
     */
    private array $messageData;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getFromName(): string
    {
        return $this->fromName;
    }

    public function setFromName(string $fromName): void
    {
        $this->fromName = $fromName;
    }

    public function getFromId(): string
    {
        return $this->fromId;
    }

    public function setFromId(string $fromId): void
    {
        $this->fromId = $fromId;
    }

    public function getGroupId(): ?string
    {
        return $this->groupId;
    }

    public function setGroupId(?string $groupId): void
    {
        $this->groupId = $groupId;
    }

    public function getImageUrl(): string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(string $imageUrl): void
    {
        $this->imageUrl = $imageUrl;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): void
    {
        $this->date = $date;
    }

    public function getCaption(): ?string
    {
        return $this->caption;
    }

    public function setCaption(?string $caption): void
    {
        $this->caption = $caption;
    }

    public function getMessageData(): array
    {
        return $this->messageData;
    }

    public function setMessageData(array $messageData): void
    {
        $this->messageData = $messageData;
    }

    public function getEncryptedImageData(): ?string
    {
        if (!$this->encryptedImageData) {
            $context = stream_context_create([
                "http" => [
                    "follow_location" => true,
                ]
            ]);
            $this->encryptedImageData = file_get_contents($this->getImageUrl(), false, $context);
        }
        return $this->encryptedImageData;
    }

    public static function fromWebhook(WasenderWebhook $webhook)
    {
        $message = new self();
        $message->setMessageData($webhook->getAllData());
        $message->setFromName($webhook->getFromName());
        $message->setFromId($webhook->getFromId());
        $messageData = $webhook->getMessageObject();
        $imageData = $messageData['image'] ?? null;
        if ($imageData) {
            $message->setImageUrl($imageData['url']);
        }
        return $message;
    }
}
