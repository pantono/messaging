<?php

namespace Pantono\Messaging\Event;

use Pantono\Messaging\Model\WhatsappContact;
use Symfony\Contracts\EventDispatcher\Event;

class AbstractWhatsappContactSaveEvent extends Event
{
    private WhatsappContact $current;
    private ?WhatsappContact $previous = null;

    public function getCurrent(): WhatsappContact
    {
        return $this->current;
    }

    public function setCurrent(WhatsappContact $current): void
    {
        $this->current = $current;
    }

    public function getPrevious(): ?WhatsappContact
    {
        return $this->previous;
    }

    public function setPrevious(?WhatsappContact $previous): void
    {
        $this->previous = $previous;
    }
}
