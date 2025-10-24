<?php

namespace Pantono\Messaging\Model;

use Pantono\Contracts\Attributes\FieldName;
use Pantono\Contracts\Attributes\Lazy;
use Pantono\Contracts\Attributes\Locator;
use Pantono\Messaging\Whatsapp;

class WhatsappGroupMember
{
    private ?int $id = null;
    private int $groupId;
    private int $contactId;
    #[Locator(methodName: 'getContactById', className: Whatsapp::class), FieldName('contact_id'), Lazy]
    private WhatsappContact $contact;
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

    public function getContactId(): int
    {
        return $this->contactId;
    }

    public function setContactId(int $contactId): void
    {
        $this->contactId = $contactId;
    }

    public function getContact(): WhatsappContact
    {
        return $this->contact;
    }

    public function setContact(WhatsappContact $contact): void
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
}
