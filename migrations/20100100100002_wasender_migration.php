<?php

declare(strict_types=1);

use Pantono\Database\Migration\Base\BasePantonoMigration;

final class WasenderMigration extends BasePantonoMigration
{
    public function change(): void
    {
        $this->table($this->addTablePrefix('whatsapp_wasender_webhook'))
            ->addColumn('date', 'datetime')
            ->addColumn('event', 'string', ['null' => true])
            ->addColumn('headers', 'json')
            ->addColumn('data', 'json')
            ->addColumn('processed', 'boolean', ['default' => false])
            ->addIndex('event')
            ->create();
    }
}
