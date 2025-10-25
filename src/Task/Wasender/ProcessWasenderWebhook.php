<?php

namespace Pantono\Messaging\Task\Wasender;

use Pantono\Messaging\Event\Wasender\WasenderWebhookProcess;
use Pantono\Messaging\Model\Wasender\WasenderWebhook;
use Pantono\Messaging\Model\WhatsappContact;
use Pantono\Messaging\Model\WhatsappGroup;
use Pantono\Messaging\Model\WhatsappGroupMember;
use Pantono\Messaging\Model\WhatsappMessage;
use Pantono\Messaging\Model\WhatsappMessageType;
use Pantono\Messaging\Service\WasenderService;
use Pantono\Messaging\Whatsapp;
use Pantono\Queue\Task\AbstractTask;
use Pantono\Storage\FileStorage;
use Pantono\Storage\Model\StoredFile;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\ParameterBag;

class ProcessWasenderWebhook extends AbstractTask
{
    private Whatsapp $whatsapp;
    private WasenderService $service;
    private FileStorage $fileStorage;
    private EventDispatcher $dispatcher;

    public function __construct(Whatsapp $whatsapp, WasenderService $service, FileStorage $storage, EventDispatcher $dispatcher)
    {
        $this->whatsapp = $whatsapp;
        $this->service = $service;
        $this->fileStorage = $storage;
        $this->dispatcher = $dispatcher;
    }

    public function process(ParameterBag $parameters): array
    {
        $id = $parameters->get('id');
        $webhook = $this->service->getWebhookById($id);
        if (!$webhook) {
            return ['success' => false, 'error' => 'Webhook not found'];
        }
        $dataParams = new ParameterBag($webhook->getData());
        $sessionId = $dataParams->get('sessionId');
        if (!$sessionId) {
            return ['success' => false, 'error' => 'No session id'];
        }
        $instance = $this->whatsapp->getInstanceByMetaValue('apiKey', $sessionId);
        if (!$instance) {
            $all = $this->whatsapp->getAllInstances();
            if (count($all) === 1) {
                $instance = $all[0];
            }
        }

        if ($instance === null) {
            return ['success' => false, 'error' => 'Instance for API key not found'];
        }

        $event = new WasenderWebhookProcess();
        $event->setWebhook($webhook);
        $event->setInstance($instance);
        $this->dispatcher->dispatch($event);
        if ($event->isProcessed()) {
            $webhook->setProcessed(true);
            $this->service->saveWebhook($webhook);
        }

        return ['success' => true, 'processed' => $event->isProcessed()];
    }
}
