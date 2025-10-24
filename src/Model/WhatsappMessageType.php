<?php

namespace Pantono\Messaging\Model;

use Pantono\Contracts\Attributes\Locator;
use Pantono\Messaging\Whatsapp;

#[Locator(methodName: 'getMessageTypeById', className: Whatsapp::class)]
class WhatsappMessageType
{
    private ?int $id = null;
    private string $name;
    private bool $text;
    private bool $album;
    private bool $image;
    private bool $poll;
    private bool $contact;
    private bool $location;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function isText(): bool
    {
        return $this->text;
    }

    public function setText(bool $text): void
    {
        $this->text = $text;
    }

    public function isAlbum(): bool
    {
        return $this->album;
    }

    public function setAlbum(bool $album): void
    {
        $this->album = $album;
    }

    public function isImage(): bool
    {
        return $this->image;
    }

    public function setImage(bool $image): void
    {
        $this->image = $image;
    }

    public function isPoll(): bool
    {
        return $this->poll;
    }

    public function setPoll(bool $poll): void
    {
        $this->poll = $poll;
    }

    public function isContact(): bool
    {
        return $this->contact;
    }

    public function setContact(bool $contact): void
    {
        $this->contact = $contact;
    }

    public function isLocation(): bool
    {
        return $this->location;
    }

    public function setLocation(bool $location): void
    {
        $this->location = $location;
    }
}
