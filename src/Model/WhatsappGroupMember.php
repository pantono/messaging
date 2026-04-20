<?php

namespace Pantono\Messaging\Model;

use Pantono\Contracts\Attributes\Database\OneToOne;
use Pantono\Contracts\Attributes\DatabaseTable;
use Pantono\Contracts\Attributes\FieldName;

#[DatabaseTable('whatsapp_group_member')]
class WhatsappGroupMember
{
    private ?int $id = null;
    private int $groupId;
    private ?int $contactId = null;
    #[OneToOne(targetModel: WhatsappContact::class), FieldName('contact_id')]
    private ?WhatsappContact $contact = null;
    private string $lid;
    private bool $isAdmin;
    private bool $isSuperAdmin;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getGroupId(): int
    {
        return $this->groupId;
    }

    public function setGroupId(int $groupId): void
    {
        $this->groupId = $groupId;
    }

    public function getContactId(): ?int
    {
        return $this->contactId;
    }

    public function setContactId(?int $contactId): void
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

    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    public function setIsAdmin(bool $isAdmin): void
    {
        $this->isAdmin = $isAdmin;
    }

    public function isSuperAdmin(): bool
    {
        return $this->isSuperAdmin;
    }

    public function setIsSuperAdmin(bool $isSuperAdmin): void
    {
        $this->isSuperAdmin = $isSuperAdmin;
    }

    public function getLid(): string
    {
        return $this->lid;
    }

    public function setLid(string $lid): void
    {
        $this->lid = $lid;
    }
}
