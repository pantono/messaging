<?php

namespace Pantono\Messaging\Event;

use Pantono\Messaging\Model\WhatsappMessage;
use Symfony\Contracts\EventDispatcher\Event;

class AbstractWhatsappMessageSaveEvent extends Event
{
    private WhatsappMessage $current;
    private ?WhatsappMessage $previous = null;

    public function getCurrent(): WhatsappMessage
    {
        return $this->current;
    }

    public function setCurrent(WhatsappMessage $current): void
    {
        $this->current = $current;
    }

    public function getPrevious(): ?WhatsappMessage
    {
        return $this->previous;
    }

    public function setPrevious(?WhatsappMessage $previous): void
    {
        $this->previous = $previous;
    }
}
