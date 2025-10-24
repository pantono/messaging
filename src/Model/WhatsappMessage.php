<?php

namespace Pantono\Messaging\Model;

use Pantono\Contracts\Attributes\FieldName;
use Pantono\Contracts\Attributes\Filter;
use Pantono\Contracts\Attributes\Lazy;
use Pantono\Contracts\Attributes\Locator;
use Pantono\Database\Traits\SavableModel;
use Pantono\Images\Images;
use Pantono\Images\Model\Image;
use Pantono\Messaging\Whatsapp;
use Pantono\Storage\FileStorage;
use Pantono\Storage\Model\StoredFile;

#[Locator(methodName: 'getMessageById', className: Whatsapp::class)]
class WhatsappMessage
{
    use SavableModel;

    private ?int $id = null;
    private int $instanceId;
    private \DateTimeInterface $date;
    #[Locator(methodName: 'getMessageTypeById', className: Whatsapp::class), Lazy, FieldName('type_id')]
    private ?WhatsappMessageType $type = null;
    #[Locator(methodName: 'getGroupById', className: Whatsapp::class), Lazy, FieldName('group_id')]
    private ?WhatsappGroup $group = null;
    private string $messageId;
    private string $contactId;
    #[Locator(methodName: 'getContactById', className: Whatsapp::class), Lazy, FieldName('contact_id')]
    private ?WhatsappContact $contact = null;
    private bool $incoming;
    private string $textContent;
    /**
     * @var array<string,mixed>
     */
    #[Filter('json_decode')]
    private array $meta;
    #[Locator(methodName: 'getMessageById', className: Whatsapp::class), Lazy, FieldName('parent_id')]
    private ?WhatsappMessage $parentId = null;
    #[Locator(methodName: 'getFileById', className: FileStorage::class), Lazy, FieldName('file_id')]
    private ?StoredFile $file = null;
    private ?string $replyTo = null;
    #[Locator(methodName: 'getMessageById', className: Whatsapp::class), Lazy, FieldName('reply_to')]
    private ?WhatsappMessage $replyToMessage = null;
    private ?string $status = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getInstanceId(): int
    {
        return $this->instanceId;
    }

    public function setInstanceId(int $instanceId): void
    {
        $this->instanceId = $instanceId;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): void
    {
        $this->date = $date;
    }

    public function getType(): ?WhatsappMessageType
    {
        return $this->type;
    }

    public function setType(?WhatsappMessageType $type): void
    {
        $this->type = $type;
    }

    public function getGroup(): ?WhatsappGroup
    {
        return $this->group;
    }

    public function setGroup(?WhatsappGroup $group): void
    {
        $this->group = $group;
    }

    public function getMessageId(): string
    {
        return $this->messageId;
    }

    public function setMessageId(string $messageId): void
    {
        $this->messageId = $messageId;
    }

    public function getContactId(): string
    {
        return $this->contactId;
    }

    public function setContactId(string $contactId): void
    {
        $this->contactId = $contactId;
    }

    public function getContact(): ?WhatsappContact
    {
        return $this->contact;
    }

    public function setContact(?WhatsappContact $contact): void
    {
        $this->contact = $contact;
    }

    public function isIncoming(): bool
    {
        return $this->incoming;
    }

    public function setIncoming(bool $incoming): void
    {
        $this->incoming = $incoming;
    }

    public function getTextContent(): string
    {
        return $this->textContent;
    }

    public function setTextContent(string $textContent): void
    {
        $this->textContent = $textContent;
    }

    public function getMeta(): array
    {
        return $this->meta;
    }

    public function setMeta(array $meta): void
    {
        $this->meta = $meta;
    }

    public function getParentId(): ?WhatsappMessage
    {
        return $this->parentId;
    }

    public function setParentId(?WhatsappMessage $parentId): void
    {
        $this->parentId = $parentId;
    }

    public function getFile(): ?StoredFile
    {
        return $this->file;
    }

    public function setFile(?StoredFile $file): void
    {
        $this->file = $file;
    }

    public function getReplyTo(): ?string
    {
        return $this->replyTo;
    }

    public function setReplyTo(?string $replyTo): void
    {
        $this->replyTo = $replyTo;
    }

    public function getReplyToMessage(): ?WhatsappMessage
    {
        return $this->replyToMessage;
    }

    public function setReplyToMessage(?WhatsappMessage $replyToMessage): void
    {
        $this->replyToMessage = $replyToMessage;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }
}
