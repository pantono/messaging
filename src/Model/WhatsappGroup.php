<?php

namespace Pantono\Messaging\Model;

use Pantono\Contracts\Attributes\Locator;
use Pantono\Database\Traits\SavableModel;
use Pantono\Messaging\Whatsapp;

#[Locator(methodName: 'getGroupById', className: Whatsapp::class)]
class WhatsappGroup
{
    use SavableModel;

    private ?int $id = null;
    private int $instanceId;
    private string $subject;
    private string $ownerId;
    private ?string $description = null;
    /**
     * @var WhatsappGroupMember[]
     */
    #[Locator(methodName: 'getMembersInGroup', className: Whatsapp::class)]
    private array $members = [];

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

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    public function getOwnerId(): string
    {
        return $this->ownerId;
    }

    public function setOwnerId(string $ownerId): void
    {
        $this->ownerId = $ownerId;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getMembers(): array
    {
        return $this->members;
    }

    public function setMembers(array $members): void
    {
        $this->members = $members;
    }

    public function addMember(WhatsappGroupMember $member): void
    {
        $this->members[] = $member;
    }
}
