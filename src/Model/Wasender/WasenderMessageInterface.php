<?php

namespace Pantono\Messaging\Model\Wasender;

interface WasenderMessageInterface
{
    public function getId(): string;
    public function getFromName(): string;
    public function getFromId(): string;
    public function getGroupId(): string;
    public function getMessageData(): array;
}
