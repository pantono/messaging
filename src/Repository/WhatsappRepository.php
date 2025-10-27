<?php

namespace Pantono\Messaging\Repository;

use Pantono\Database\Repository\MysqlRepository;
use Pantono\Messaging\Model\WhatsappContact;
use Pantono\Messaging\Model\WhatsappGroup;
use Pantono\Messaging\Model\WhatsappInstance;
use Pantono\Messaging\Model\WhatsappMessage;
use Pantono\Messaging\WhatsappMessageFilter;

class WhatsappRepository extends MysqlRepository
{
    public function getContactById(int $id): ?array
    {
        $select = $this->getDb()->select()->from('whatsapp_contact')
            ->where('id=?', $id)
            ->setLockForUpdate(true);
        return $this->selectSingleRowFromQuery($select);
    }

    public function getContactByWhatsappId(WhatsappInstance $instance, string $phoneNumber): ?array
    {
        $select = $this->getDb()->select()->from('whatsapp_contact')
            ->where('instance_id=?', $instance->getId())
            ->where('whatsapp_id=?', $phoneNumber)
            ->setLockForUpdate(true);
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
        $select = $this->getDb()->select()->from('whatsapp_message');

        if ($filter->getContact() !== null) {
            $select->where('whatsapp_message.contact_id=?', $filter->getContact()->getId());
        }

        if ($filter->getStartDate() !== null) {
            $select->where('whatsapp_message.date >= ?', $filter->getStartDate()->format('Y-m-d H:i:s'));
        }

        if ($filter->getEndDate() !== null) {
            $select->where('whatsapp_message.date <= ?', $filter->getEndDate()->format('Y-m-d H:i:s'));
        }

        if ($filter->getType() !== null) {
            $select->where('whatsapp_message.type_id=?', $filter->getType()->getId());
        }

        if ($filter->getWhatsappContactId() !== null) {
            $select->joinInner('whatsapp_contact', 'whatsapp_contact.id = whatsapp_message.contact_id', [])
                ->where('whatsapp_contact.whatsapp_id = ?', $filter->getWhatsappContactId());
        }

        if ($filter->getSearch() !== null) {
            $select->where('whatsapp_message.text_content LIKE ?', '%' . $filter->getSearch() . '%');
        }

        $filter->setTotalResults($this->getCount($select));

        $select->limitPage($filter->getPage(), $filter->getPerPage());

        return $this->getDb()->fetchAll($select);
    }

    public function getMessageById(int $id): ?array
    {
        return $this->selectSingleRow('whatsapp_message', 'id', $id);
    }

    public function getMessageByWhatsappId(int $instanceId, string $whatsappId): ?array
    {
        return $this->selectRowByValues('whatsapp_message', ['instance_id' => $instanceId, 'message_id' => $whatsappId]);
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
                'contact_id' => $member->getContact()->getId(),
                'is_admin' => $member->isAdmin() ? 1 : 0,
                'is_super_admin' => $member->isSuperAdmin() ? 1 : 0,
            ]);
        }
    }

    public function getInstanceByMetaValue(string $key, string $value): ?array
    {
        $select = $this->getDb()->select()->from('whatsapp_instance')
            ->where("metadata->>'.$key' = ?", $value);

        return $this->selectSingleRowFromQuery($select);
    }
}
