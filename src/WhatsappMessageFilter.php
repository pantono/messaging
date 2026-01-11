<?php

namespace Pantono\Messaging;

use Pantono\Contracts\Filter\PageableInterface;
use Pantono\Database\Traits\Pageable;
use Pantono\Messaging\Model\WhatsappContact;
use Pantono\Messaging\Model\WhatsappMessageType;

class WhatsappMessageFilter implements PageableInterface
{
    use Pageable;

    private ?string $whatsappContactId = null;
    private ?WhatsappContact $contact = null;
    private ?\DateTimeInterface $startDate = null;
    private ?\DateTimeInterface $endDate = null;
    private ?WhatsappMessageType $type = null;
    private ?string $search = null;
    private ?bool $direct = null;
    private ?int $groupId = null;

    public function getWhatsappContactId(): ?string
    {
        return $this->whatsappContactId;
    }

    public function setWhatsappContactId(?string $whatsappContactId): void
    {
        $this->whatsappContactId = $whatsappContactId;
    }

    public function getContact(): ?WhatsappContact
    {
        return $this->contact;
    }

    public function setContact(?WhatsappContact $contact): void
    {
        $this->contact = $contact;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): void
    {
        $this->startDate = $startDate;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): void
    {
        $this->endDate = $endDate;
    }

    public function getType(): ?WhatsappMessageType
    {
        return $this->type;
    }

    public function setType(?WhatsappMessageType $type): void
    {
        $this->type = $type;
    }

    public function getSearch(): ?string
    {
        return $this->search;
    }

    public function setSearch(?string $search): void
    {
        $this->search = $search;
    }

    public function getDirect(): ?bool
    {
        return $this->direct;
    }

    public function setDirect(?bool $direct): void
    {
        $this->direct = $direct;
    }

    public function getGroupId(): ?int
    {
        return $this->groupId;
    }

    public function setGroupId(?int $groupId): void
    {
        $this->groupId = $groupId;
    }
}
