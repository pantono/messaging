<?php

namespace Pantono\Messaging\Model;

use Pantono\Contracts\Attributes\FieldName;
use Pantono\Contracts\Attributes\Locator;
use Pantono\Database\Traits\SavableModel;
use Pantono\Messaging\Whatsapp;

#[Locator(className: Whatsapp::class, methodName: 'getContactById')]
class WhatsappContact
{
    use SavableModel;

    private ?int $id = null;
    #[FieldName('instance_id'), Locator(methodName: 'getInstanceById', className: Whatsapp::class)]
    private WhatsappInstance $instance;
    private string $whatsappId;
    private string $name;
    private string $status;
    private bool $online;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getInstance(): WhatsappInstance
    {
        return $this->instance;
    }

    public function setInstance(WhatsappInstance $instance): void
    {
        $this->instance = $instance;
    }

    public function getWhatsappId(): string
    {
        return $this->whatsappId;
    }

    public function setWhatsappId(string $whatsappId): void
    {
        $this->whatsappId = $whatsappId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function isOnline(): bool
    {
        return $this->online;
    }

    public function setOnline(bool $online): void
    {
        $this->online = $online;
    }
}
