<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class WasenderMigration extends AbstractMigration
{
    public function change(): void
    {
        $this->table('whatsapp_wasender_webhook')
            ->addColumn('date', 'datetime')
            ->addColumn('event', 'string', ['null' => true])
            ->addColumn('headers', 'json')
            ->addColumn('data', 'json')
            ->addColumn('processed', 'boolean', ['default' => false])
            ->addIndex('event')
            ->create();
    }
}
