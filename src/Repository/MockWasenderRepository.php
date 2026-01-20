<?php

namespace Pantono\Messaging\Repository;

use Pantono\Database\Repository\DefaultRepository;

class MockWasenderRepository extends DefaultRepository
{
    public function logCall(int $instanceId, string $method, array $data): void
    {
        $this->getDb()->insert('whatsapp_service_mock', [
            'instance_id' => $instanceId,
            'date' => (new \DateTime())->format('Y-m-d H:i:s'),
            'action' => $method,
            'data' => json_encode($data)
        ]);
    }
}
