<?php

namespace Pantono\Messaging\Repository;

use Pantono\Database\Repository\MysqlRepository;

class MockWasenderRepository extends MysqlRepository
{
    public function logCall(int $instanceId, string $method, array $data): void
    {
        $this->getDb()->insert('whatsapp_service_mock', [
            'instance_id' => $instanceId,
            'method' => $method,
            'data' => json_encode($data)
        ]);
    }
}
