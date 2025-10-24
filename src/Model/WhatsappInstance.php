<?php

namespace Pantono\Messaging\Model;

use Pantono\Contracts\Attributes\Filter;
use Pantono\Contracts\Attributes\Locator;
use Pantono\Messaging\Whatsapp;

#[Locator(methodName: Whatsapp::class, className: 'getInstanceById')]
class WhatsappInstance
{
    private ?int $id = null;
    private string $service;
    private string $phoneNumber;
    private string $name;
    /**
     * @var array<string,mixed>
     */
    #[Filter('json_decode')]
    private array $metadata = [];
    private bool $default;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getService(): string
    {
        return $this->service;
    }

    public function setService(string $service): void
    {
        $this->service = $service;
    }

    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return array<string,mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function getMetaValue(string $name, mixed $default = null): ?string
    {
        return $this->metadata[$name] ?? $default;
    }

    public function isDefault(): bool
    {
        return $this->default;
    }

    public function setDefault(bool $default): void
    {
        $this->default = $default;
    }
}
