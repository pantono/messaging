<?php

namespace Pantono\Messaging;

use Pantono\Hydrator\Locator\StaticLocator;
use Pantono\Messaging\Event\PostWhatsappContactSaveEvent;
use Pantono\Messaging\Event\PostWhatsappGroupSaveEvent;
use Pantono\Messaging\Event\PostWhatsappMessageSaveEvent;
use Pantono\Messaging\Event\PreWhatsappContactSaveEvent;
use Pantono\Messaging\Event\PreWhatsappGroupSaveEvent;
use Pantono\Messaging\Event\PreWhatsappMessageSaveEvent;
use Pantono\Messaging\Model\WhatsappContact;
use Pantono\Messaging\Model\WhatsappGroup;
use Pantono\Messaging\Model\WhatsappGroupMember;
use Pantono\Messaging\Model\WhatsappInstance;
use Pantono\Messaging\Model\WhatsappMessage;
use Pantono\Messaging\Model\WhatsappMessageType;
use Pantono\Messaging\Repository\WhatsappRepository;
use Pantono\Hydrator\Hydrator;
use Pantono\Messaging\Service\WhatsappServiceInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Whatsapp
{
    private WhatsappRepository $repository;
    private Hydrator $hydrator;
    private EventDispatcher $dispatcher;
    public const int MESSAGE_TYPE_TEXT = 1;
    public const int MESSAGE_TYPE_ALBUM = 2;
    public const int MESSAGE_TYPE_IMAGE = 3;
    public const int MESSAGE_TYPE_POLL = 4;
    public const int MESSAGE_TYPE_CONTACT = 5;
    public const int MESSAGE_TYPE_LOCATION = 6;
    public const int MESSAGE_TYPE_STICKER = 7;
    public const int MESSAGE_TYPE_AUDIO = 8;
    public const int MESSAGE_TYPE_REACTION = 9;

    public function __construct(WhatsappRepository $repository, Hydrator $hydrator, EventDispatcher $dispatcher)
    {
        $this->repository = $repository;
        $this->hydrator = $hydrator;
        $this->dispatcher = $dispatcher;
    }

    public function getContactById(int $id): ?WhatsappContact
    {
        return $this->hydrator->hydrate(WhatsappContact::class, $this->repository->getContactById($id));
    }
    public function getContactByWhatsappId(WhatsappInstance $instance, string $id): ?WhatsappContact
    {
        return $this->hydrator->hydrate(WhatsappContact::class, $this->repository->getContactByWhatsappId($instance, $id));
    }

    public function getInstanceById(int $id): ?WhatsappInstance
    {
        return $this->hydrator->hydrate(WhatsappInstance::class, $this->repository->getInstanceById($id));
    }

    public function getAllInstances(): array
    {
        return $this->hydrator->hydrateSet(WhatsappInstance::class, $this->repository->getAllInstances());
    }

    public function getInstanceByMetaValue(string $key, string $value): ?WhatsappInstance
    {
        $instances = $this->getAllInstances();
        foreach ($instances as $instance) {
            if ($instance->getMetaValue($key) === $value) {
                return $instance;
            }
        }
        return null;
    }

    public function getDefaultInstance(): ?WhatsappInstance
    {
        return $this->hydrator->hydrate(WhatsappInstance::class, $this->repository->getDefaultInstance());
    }

    public function getGroupById(int $id): ?WhatsappGroup
    {
        return $this->hydrator->hydrate(WhatsappGroup::class, $this->repository->getGroupById($id));
    }
    public function getGroupByWhatsappId(WhatsappInstance $instance, string $id): ?WhatsappGroup
    {
        return $this->hydrator->hydrate(WhatsappGroup::class, $this->repository->getGroupByWhatsAppId($instance->getId(), $id));
    }

    public function getMembersInGroup(WhatsappGroup $group): array
    {
        return $this->hydrator->hydrateSet(WhatsappGroupMember::class, $this->repository->getMembersInGroup($group));
    }

    public function getMessageTypeById(int $id): ?WhatsappMessageType
    {
        return $this->hydrator->hydrate(WhatsappMessageType::class, $this->repository->getMessageTypeById($id));
    }

    public function getMessagesByFilter(WhatsappMessageFilter $filter): array
    {
        return $this->hydrator->hydrateSet(WhatsappMessage::class, $this->repository->getMessagesByFilter($filter));
    }

    public function getMessageById(int $id): ?WhatsappMessage
    {
        return $this->hydrator->hydrate(WhatsappMessage::class, $this->repository->getMessageById($id));
    }

    public function getMessageByWhatsappId(int $instanceId, string $whatsappId): ?WhatsappMessage
    {
        return $this->hydrator->hydrate(WhatsappMessage::class, $this->repository->getMessageByWhatsappId($instanceId, $whatsappId));
    }

    public function saveMessage(WhatsappMessage $message): void
    {
        $previous = $message->getId() ? $this->getMessageById($message->getId()) : null;
        $event = new PreWhatsappMessageSaveEvent();
        $event->setCurrent($message);
        $event->setPrevious($previous);
        $this->dispatcher->dispatch($event);

        $this->repository->saveMessage($message);

        $event = new PostWhatsappMessageSaveEvent();
        $event->setCurrent($message);
        $event->setPrevious($previous);
        $this->dispatcher->dispatch($event);
    }

    public function saveContact(WhatsappContact $contact): void
    {
        $previous = $contact->getId() ? $this->getContactById($contact->getId()) : null;
        $event = new PreWhatsappContactSaveEvent();
        $event->setCurrent($contact);
        $event->setPrevious($previous);
        $this->dispatcher->dispatch($event);

        $this->repository->saveContact($contact);

        $event = new PostWhatsappContactSaveEvent();
        $event->setCurrent($contact);
        $event->setPrevious($previous);
        $this->dispatcher->dispatch($event);
    }

    public function saveGroup(WhatsappGroup $group): void
    {
        $previous = $group->getId() ? $this->getGroupById($group->getId()) : null;
        $event = new PreWhatsappGroupSaveEvent();
        $event->setCurrent($group);
        $event->setPrevious($previous);
        $this->dispatcher->dispatch($event);

        $this->repository->saveGroup($group);

        $event = new PostWhatsappGroupSaveEvent();
        $event->setCurrent($group);
        $event->setPrevious($previous);
        $this->dispatcher->dispatch($event);
    }
    public function getDefaultService(): WhatsappServiceInterface
    {
        $instance = $this->getDefaultInstance();
        if (!$instance) {
            throw new \RuntimeException('No default instance found');
        }
        return $this->getServiceForInstance($instance);
    }

    public function getServiceForInstance(WhatsappInstance $instance): WhatsappServiceInterface
    {
        $service = StaticLocator::getLocator()->loadDependency('@' . $instance->getService());
        if (!$service) {
            throw new \RuntimeException(sprintf('Whatsapp Service %s does not exist', $service));
        }
        if (!$service instanceof WhatsappServiceInterface) {
            throw new \RuntimeException(sprintf('Whatsapp Service %s class not implement WhatsappServiceInterface', $service));
        }
        $service->setInstance($instance);
        return $service;
    }
}
