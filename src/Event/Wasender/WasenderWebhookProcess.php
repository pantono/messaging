<?php

namespace Pantono\Messaging\Event\Wasender;

use Pantono\Messaging\Model\Wasender\WasenderWebhook;
use Pantono\Messaging\Model\WhatsappInstance;
use Symfony\Contracts\EventDispatcher\Event;

class WasenderWebhookProcess extends Event
{
    private WasenderWebhook $webhook;
    private bool $processed = false;
    private ?WhatsappInstance $instance = null;

    public function getWebhook(): WasenderWebhook
    {
        return $this->webhook;
    }

    public function setWebhook(WasenderWebhook $webhook): void
    {
        $this->webhook = $webhook;
    }

    public function isProcessed(): bool
    {
        return $this->processed;
    }

    public function setProcessed(bool $processed): void
    {
        $this->processed = $processed;
    }

    public function getInstance(): ?WhatsappInstance
    {
        return $this->instance;
    }

    public function setInstance(?WhatsappInstance $instance): void
    {
        $this->instance = $instance;
    }
}
