<?php

namespace Pantono\Messaging\Event;

use Pantono\Messaging\Model\WhatsappGroup;
use Symfony\Contracts\EventDispatcher\Event;

class AbstractWhatsappGroupSaveEvent extends Event
{
    private WhatsappGroup $current;
    private ?WhatsappGroup $previous = null;

    public function getCurrent(): WhatsappGroup
    {
        return $this->current;
    }

    public function setCurrent(WhatsappGroup $current): void
    {
        $this->current = $current;
    }

    public function getPrevious(): ?WhatsappGroup
    {
        return $this->previous;
    }

    public function setPrevious(?WhatsappGroup $previous): void
    {
        $this->previous = $previous;
    }
}
