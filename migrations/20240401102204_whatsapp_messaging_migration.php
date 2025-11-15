<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class WhatsappMessagingMigration extends AbstractMigration
{
    public function change(): void
    {
        $this->table('whatsapp_instance')
            ->addColumn('phone_number', 'string')
            ->addColumn('service', 'string')
            ->addColumn('name', 'string')
            ->addColumn('metadata', 'json')
            ->addColumn('default', 'boolean')
            ->addIndex('phone_number', ['unique' => true])
            ->create();

        $this->table('whatsapp_message_type')
            ->addColumn('name', 'string')
            ->addColumn('text', 'boolean', ['default' => 0])
            ->addColumn('album', 'boolean', ['default' => 0])
            ->addColumn('image', 'boolean', ['default' => 0])
            ->addColumn('poll', 'boolean', ['default' => 0])
            ->addColumn('contact', 'boolean', ['default' => 0])
            ->addColumn('location', 'boolean', ['default' => 0])
            ->addColumn('audio', 'boolean', ['default' => 0])
            ->addColumn('reaction', 'boolean', ['default' => 0])
            ->create();

        if ($this->isMigratingUp()) {
            $this->table('whatsapp_message_type')
                ->insert([
                    ['id' => 1, 'name' => 'Text', 'text' => 1],
                    ['id' => 2, 'name' => 'Album', 'album' => 1],
                    ['id' => 3, 'name' => 'Image', 'image' => 1],
                    ['id' => 4, 'name' => 'Poll', 'poll' => 1],
                    ['id' => 5, 'name' => 'Contact', 'contact' => 1],
                    ['id' => 6, 'name' => 'Location', 'location' => 1],
                    ['id' => 7, 'name' => 'Sticker', 'image' => 1],
                    ['id' => 8, 'name' => 'Audio', 'audio' => 1],
                    ['id' => 9, 'name' => 'Reaction', 'reaction' => 1],
                ])->saveData();
        }
        $this->table('whatsapp_contact')
            ->addColumn('instance_id', 'integer', ['signed' => false])
            ->addColumn('whatsapp_id', 'string')
            ->addColumn('name', 'string')
            ->addColumn('status', 'string', ['null' => true])
            ->addColumn('online', 'boolean')
            ->addIndex('whatsapp_id', ['unique' => true])
            ->addForeignKey('instance_id', 'whatsapp_instance', 'id')
            ->create();

        $this->table('whatsapp_group')
            ->addColumn('instance_id', 'integer', ['signed' => false])
            ->addColumn('group_id', 'string', ['signed' => false])
            ->addColumn('subject', 'string')
            ->addColumn('owner_id', 'string')
            ->addColumn('description', 'string', ['null' => true])
            ->addIndex('group_id', ['unique' => true])
            ->addForeignKey('instance_id', 'whatsapp_instance', 'id')
            ->create();

        $this->table('whatsapp_group_member', ['id' => false])
            ->addColumn('group_id', 'integer', ['signed' => false])
            ->addColumn('contact_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('is_admin', 'boolean', ['default' => false])
            ->addColumn('is_super_admin', 'boolean', ['default' => false])
            ->addColumn('lid', 'string', ['null' => false])
            ->addIndex(['group_id', 'contact_id'], ['unique' => true])
            ->addForeignKey('group_id', 'whatsapp_group', 'id')
            ->addForeignKey('contact_id', 'whatsapp_contact', 'id')
            ->addIndex('lid')
            ->create();

        $this->table('whatsapp_message')
            ->addColumn('instance_id', 'integer', ['signed' => false])
            ->addColumn('date', 'datetime')
            ->addColumn('type_id', 'integer', ['signed' => false])
            ->addColumn('group_id', 'integer', ['signed' => false])
            ->addColumn('message_id', 'string', ['null' => true])
            ->addColumn('contact_id', 'integer', ['signed' => false])
            ->addColumn('incoming', 'boolean')
            ->addColumn('text_content', 'text', ['null' => true])
            ->addColumn('meta', 'json')
            ->addColumn('parent_id', 'string', ['null' => true, 'signed' => false])
            ->addColumn('file_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('reply_to', 'string', ['null' => true, 'signed' => false])
            ->addColumn('status', 'string', ['null' => true])
            ->addIndex('message_id', ['unique' => true])
            ->addForeignKey('instance_id', 'whatsapp_instance', 'id')
            ->addForeignKey('type_id', 'whatsapp_message_type', 'id')
            ->addForeignKey('group_id', 'whatsapp_group', 'id')
            ->addForeignKey('contact_id', 'whatsapp_contact', 'id')
            ->addForeignKey('parent_id', 'whatsapp_message', 'message_id')
            ->addForeignKey('reply_to', 'whatsapp_message', 'message_id')
            ->addForeignKey('file_id', 'stored_file', 'id')
            ->create();
    }
}
