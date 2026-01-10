<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class WhatsappServiceMockMigration extends AbstractMigration
{
    public function change(): void
    {
        $this->table('whatsapp_service_mock')
            ->addColumn('instance_id', 'integer')
            ->addColumn('date', 'datetime')
            ->addColumn('action', 'string')
            ->addColumn('data', 'json')
            ->create();
    }
}
