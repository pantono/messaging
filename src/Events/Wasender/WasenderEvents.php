<?php

namespace Pantono\Messaging\Events\Wasender;

use Pantono\Messaging\Event\Wasender\PostWasenderWebhookSaveEvent;
use Pantono\Messaging\Service\WasenderService;
use Pantono\Queue\QueueManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class WasenderEvents implements EventSubscriberInterface
{
    private QueueManager $queueManager;

    public function __construct(QueueManager $queueManager)
    {
        $this->queueManager = $queueManager;
    }
    public static function getSubscribedEvents(): array
    {
        return [
            PostWasenderWebhookSaveEvent::class => [
                ['createTask', 0]
            ]
        ];
    }

    public function createTask(PostWasenderWebhookSaveEvent $event): void
    {
        if (!$event->getPrevious()) {
            $this->queueManager->createTask('wasender_webhook', ['id' => $event->getCurrent()->getId(), 'data' => $event->getCurrent()->getAllData()]);
        }
    }
}
