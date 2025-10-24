<?php

namespace Pantono\Messaging\Event\Wasender;

use Pantono\Messaging\Model\Wasender\WasenderWebhook;
use Symfony\Contracts\EventDispatcher\Event;

class AbstractWasenderWebhookSaveEvent extends Event
{
    private WasenderWebhook $current;
    private ?WasenderWebhook $previous = null;

    public function getCurrent(): WasenderWebhook
    {
        return $this->current;
    }

    public function setCurrent(WasenderWebhook $current): void
    {
        $this->current = $current;
    }

    public function getPrevious(): ?WasenderWebhook
    {
        return $this->previous;
    }

    public function setPrevious(?WasenderWebhook $previous): void
    {
        $this->previous = $previous;
    }
}
