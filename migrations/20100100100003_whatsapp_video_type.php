<?php

declare(strict_types=1);

use Pantono\Database\Migration\Base\BasePantonoMigration;

final class WhatsappVideoType extends BasePantonoMigration
{
    public function change(): void
    {
        $this->table($this->addTablePrefix('whatsapp_message_type'))
            ->addColumn('video', 'boolean', ['default' => 0])
            ->update();

        if ($this->isMigratingUp()) {
            $this->table($this->addTablePrefix('whatsapp_message_type'))
                ->insert([
                    ['id' => 10, 'name' => 'Video', 'video' => 1],
                ])->update();
        }
    }
}
