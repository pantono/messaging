<?php

namespace Pantono\Messaging\Repository;

use Pantono\Database\Repository\DefaultRepository;
use Pantono\Messaging\Model\WhatsappContact;
use Pantono\Messaging\Model\WhatsappGroup;
use Pantono\Messaging\Model\WhatsappInstance;
use Pantono\Messaging\Model\WhatsappMessage;
use Pantono\Messaging\WhatsappMessageFilter;

class WhatsappRepository extends DefaultRepository
{
    public function getContactById(int $id): ?array
    {
        $select = $this->getDb()->select('c.*')->from('whatsapp_contact', 'c')
            ->forUpdate()
            ->andWhere('c.id=:id')
            ->setParameter('id', $id);

        return $this->getDb()->fetchRow($select);
    }

    public function getContactByWhatsappId(WhatsappInstance $instance, string $phoneNumber): ?array
    {
        $select = $this->getDb()->select('c.*')->from('whatsapp_contact', 'c')
            ->forUpdate()
            ->andWhere('c.instance_id=:instance_id')
            ->andWhere('c.whatsapp_id=:whatsapp_id')
            ->setParameter('instance_id', $instance->getId())
            ->setParameter('whatsapp_id', $phoneNumber);

        return $this->getDb()->fetchRow($select);
    }

    public function getGroupById(int $id): ?array
    {
        return $this->selectSingleRow('whatsapp_group', 'id', $id);
    }

    public function getGroupByWhatsAppId(int $instanceId, string $whatsAppId): ?array
    {
        return $this->selectRowByValues('whatsapp_group', ['instance_id' => $instanceId, 'group_id' => $whatsAppId]);
    }

    public function getMembersInGroup(WhatsappGroup $group): array
    {
        return $this->selectRowsByValues('whatsapp_group_member', ['group_id' => $group->getId()]);
    }

    public function getInstanceById(int $id): ?array
    {
        return $this->selectSingleRow('whatsapp_instance', 'id', $id);
    }

    public function getAllInstances(): ?array
    {
        return $this->selectAll('whatsapp_instance');
    }

    public function getDefaultInstance(): ?array
    {
        return $this->selectSingleRow('whatsapp_instance', 'default', 1);
    }

    public function getMessageTypeById(int $id): ?array
    {
        return $this->selectSingleRow('whatsapp_message_type', 'id', $id);
    }

    public function getMessagesByFilter(WhatsappMessageFilter $filter): array
    {
        $select = $this->getDb()->select('m.*')->from('whatsapp_message', 'm')
            ->addOrderBy('date', 'DESC');

        if ($filter->getContact() !== null) {
            $select->andWhere('m.contact_id=:contact_id')
                ->setParameter('contact_id', $filter->getContact()->getId());
        }

        if ($filter->getStartDate() !== null) {
            $select->andWhere('m.date >= :start_date')
                ->setParameter('start_date', $filter->getStartDate()->format('Y-m-d H:i:s'));
        }

        if ($filter->getEndDate() !== null) {
            $select->andWhere('m.date <= :end_date')
                ->setParameter('end_date', $filter->getEndDate()->format('Y-m-d H:i:s'));
        }

        if ($filter->getType() !== null) {
            $select->andWhere('m.type_id=:type_id')
                ->setParameter('type_id', $filter->getType()->getId());
        }

        if ($filter->getWhatsappContactId() !== null) {
            $select->innerJoin('m', 'whatsapp_contact', 'c', 'c.id = m.contact_id')
                ->andWhere('c.whatsapp_id = :id')
                ->setParameter('id', $filter->getWhatsappContactId());
        }

        if ($filter->getSearch() !== null) {
            $select->andWhere('m.text_content LIKE :search')
                ->setParameter('search', '%' . $filter->getSearch() . '%');
        }

        if ($filter->getDirect() === true) {
            $select->andWhere('m.group_id IS NULL');
        }
        if ($filter->getDirect() === false) {
            $select->andWhere('m.group_id IS NOT NULL');
        }

        if ($filter->getGroupId() !== null) {
            $select->andWhere('m.group_id=:group_id')
                ->setParameter('group_id', $filter->getGroupId());
        }

        $this->applyCountAndLimit($select, $filter);

        return $this->getDb()->fetchAll($select);
    }

    public function getMessageById(int $id): ?array
    {
        return $this->selectSingleRow('whatsapp_message', 'id', $id);
    }

    public function getMessageByWhatsappId(int $instanceId, string $whatsappId): ?array
    {
        $select = $this->getDb()->select('m.*')->from('whatsapp_message', 'm')
            ->andWhere('instance_id=:instance_id')
            ->andWhere('message_id=:whatsapp_id')
            ->setParameter('instance_id', $instanceId)
            ->setParameter('whatsapp_id', $whatsappId)
            ->forUpdate();

        return $this->getDb()->fetchRow($select);
    }

    public function getMessageByWhatsappIdStandalone(string $whatsappId): ?array
    {
        $select = $this->getDb()->select('m.*')->from('whatsapp_message', 'm')
            ->andWhere('m.message_id=:whatsapp_id')
            ->setParameter('whatsapp_id', $whatsappId)
            ->forUpdate();

        return $this->getDb()->fetchRow($select);
    }

    public function saveMessage(WhatsappMessage $message): void
    {
        $id = $this->insertOrUpdate('whatsapp_message', 'id', $message->getId(), $message->getAllData());
        if ($id) {
            $message->setId($id);
        }
    }

    public function saveContact(WhatsappContact $contact): void
    {
        $id = $this->insertOrUpdate('whatsapp_contact', 'id', $contact->getId(), $contact->getAllData());
        if ($id) {
            $contact->setId($id);
        }
    }

    public function saveGroup(WhatsappGroup $group): void
    {
        $id = $this->insertOrUpdate('whatsapp_group', 'id', $group->getId(), $group->getAllData());
        if ($id) {
            $group->setId($id);
        }

        $this->getDb()->delete('whatsapp_group_member', ['group_id' => $group->getId()]);

        foreach ($group->getMembers() as $member) {
            $this->insert('whatsapp_group_member', [
                'group_id' => $group->getId(),
                'lid' => $member->getLid(),
                'contact_id' => $member->getContact()?->getId(),
                'is_admin' => $member->isAdmin() ? 1 : 0,
                'is_super_admin' => $member->isSuperAdmin() ? 1 : 0,
            ]);
        }
    }

    public function getInstanceByMetaValue(string $key, string $value): ?array
    {
        $select = $this->getDb()->select('i.*')->from('whatsapp_instance', 'i')
            ->andWhere('metadata->>' . $key . ' = :value')
            ->setParameter('value: ', $value);

        return $this->getDb()->fetchRow($select);
    }

    public function getChildMessages(string $messageId): ?array
    {
        return $this->selectRowsByValues('whatsapp_message', ['parent_id' => $messageId]);
    }
}
