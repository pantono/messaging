<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class WhatsappVideoType extends AbstractMigration
{
    public function change(): void
    {
        $this->table('whatsapp_message_type')
            ->addColumn('video', 'boolean', ['default' => 0])
            ->update();

        if ($this->isMigratingUp()) {
            $this->table('whatsapp_message_type')
                ->insert([
                    ['id' => 10, 'name' => 'Video', 'video' => 1],
                ])->update();
        }
    }
}
