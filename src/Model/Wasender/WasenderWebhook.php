<?php

namespace Pantono\Messaging\Model\Wasender;

use Pantono\Database\Traits\SavableModel;
use Pantono\Contracts\Attributes\Filter;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class WasenderWebhook
{
    use SavableModel;

    private ?int $id = null;
    private ?string $event = null;
    private \DateTimeInterface $date;
    /**
     * @var array<string,mixed>
     */
    #[Filter('json_decode')]
    private array $data = [];
    /**
     * @var array<string,string>
     */
    #[Filter('json_decode')]
    private array $headers = [];
    private bool $processed = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getEvent(): ?string
    {
        return $this->event;
    }

    public function setEvent(?string $event): void
    {
        $this->event = $event;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): void
    {
        $this->date = $date;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    public static function fromData(array $data, array $headers): self
    {
        $event = $data['event'] ?? null;
        if ($event === null) {
            throw new \RuntimeException('EVent Updated');
        }
        $hook = new self();
        $hook->setDate(new \DateTime());
        $hook->setData($data);
        $hook->setEvent($event);
        $hook->setHeaders($headers);
        return $hook;
    }

    public static function fromRequest(Request $request): self
    {
        $body = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        return self::fromData($body, $request->headers->all());
    }

    public function getKey(): ParameterBag
    {
        $data = $this->getData();
        $keyData = $data['data']['messages']['key'] ?? null;
        if ($keyData === null) {
            throw new \RuntimeException('No key data in webhook');
        }
        return new ParameterBag($keyData);
    }

    public function isMessageHook(): bool
    {
        $messageHooks = ['messages.received', 'messages-group.received', 'messages-personal.received', 'messages.upsert'];
        if (!in_array($this->getEvent(), $messageHooks)) {
            return false;
        }
        return true;
    }

    public function getDataObject(): ParameterBag
    {
        return new ParameterBag($this->data['data'] ?? []);
    }
    public function getMessageObject(): ParameterBag
    {
        if (!$this->isMessageHook()) {
            throw new \RuntimeException('Webhook is not a message');
        }

        return new ParameterBag($this->data['data']['messages']['message'] ?? []);
    }
    public function getMessageData(): ParameterBag
    {
        if (!$this->isMessageHook()) {
            throw new \RuntimeException('Webhook is not a message');
        }

        return new ParameterBag($this->data['data']['messages'] ?? []);
    }

    public function getFromName(): ?string
    {
        if (!$this->isMessageHook()) {
            throw new \RuntimeException('Webhook is not a message');
        }
        $data = $this->getData();
        return $data['data']['messages']['pushName'] ?? null;
    }

    public function getFromId(): ?string
    {
        if (!$this->isMessageHook()) {
            throw new \RuntimeException('Webhook is not a message');
        }
        $data = $this->getData();
        return $data['data']['messages']['remoteJid'] ?? null;
    }

    public function isProcessed(): bool
    {
        return $this->processed;
    }

    public function setProcessed(bool $processed): void
    {
        $this->processed = $processed;
    }
}
