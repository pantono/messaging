<?php

namespace Pantono\Messaging\Task\Wasender;

use Pantono\Messaging\Model\WhatsappContact;
use Pantono\Messaging\Model\WhatsappGroup;
use Pantono\Messaging\Model\WhatsappInstance;
use Pantono\Messaging\Service\WasenderService;
use Pantono\Messaging\Whatsapp;
use Pantono\Queue\Task\AbstractTask;
use Symfony\Component\HttpFoundation\ParameterBag;

class UpdateWasenderGroupData extends AbstractTask
{
    private Whatsapp $whatsapp;
    private WasenderService $service;

    public function __construct(Whatsapp $whatsapp, WasenderService $service)
    {
        $this->whatsapp = $whatsapp;
        $this->service = $service;
    }
    public function process(ParameterBag $parameters): array
    {
        $groupId = $parameters->get('id');
        if (!$groupId) {
            throw new \RuntimeException('No group id provided');
        }
        $instanceId = $parameters->get('instance_id');
        $instance = $this->whatsapp->getInstanceById($instanceId);
        if (!$instance) {
            throw new \RuntimeException('No instance found for id ' . $instanceId);
        }
        $this->service->setInstance($instance);
        $this->updateGroup($instance, $groupId);
        return ['success' => true];
    }

    private function updateGroup(WhatsappInstance $instance, string $id): void
    {
        $groupData = $this->service->getGroupMetadata($id);
        if ($groupData['success'] === true) {
            $groupResponse = new ParameterBag($groupData['data']);
            $groupModel = $this->whatsapp->getGroupByWhatsappId($instance, $id);
            if (!$groupModel) {
                $groupModel = new WhatsappGroup();
                $groupModel->setInstanceId($instance->getId());
                $groupModel->setGroupId($groupResponse->get('id'));
                $groupModel->setSubject($groupResponse->get('subject'));
                $groupModel->setOwnerId($groupResponse->get('ownerJid'));
                $groupModel->setDescription('');
            }
            foreach ($groupResponse->get('participants', []) as $participant) {
                $contact = null;
                if ($participant['jid'] !== '') {
                    $contact = $this->createOrUpdateContact($instance, $participant['jid']);
                }
                $groupModel->addMember($contact, $participant['lid'], $participant['admin'] === 'admin', $participant['admin'] === 'superadmin');
            }
            $this->whatsapp->saveGroup($groupModel);
        }
    }

    private function createOrUpdateContact(WhatsappInstance $instance, string $id, ?string $name = ''): WhatsappContact
    {
        if ($name === null) {
            $name = '';
        }
        $this->whatsapp->startTransaction();
        $contact = $this->whatsapp->getContactByWhatsappId($instance, $id);
        if (!$contact) {
            $contact = new WhatsappContact();
            $contact->setInstance($instance);
            $contact->setWhatsappId($id);
            $contact->setName($name);
            $contact->setStatus('unknown');
            $contact->setOnline(false);
            $this->whatsapp->saveContact($contact);
        }
        $this->whatsapp->endTransaction();
        return $contact;
    }
}
