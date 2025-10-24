<?php

namespace Pantono\Messaging\Repository;

use Pantono\Database\Repository\MysqlRepository;
use Pantono\Messaging\Model\Wasender\WasenderWebhook;

class WasenderRepository extends MysqlRepository
{
    public function saveWebhook(WasenderWebhook $webhook): void
    {
        $id = $this->insertOrUpdate('whatsapp_wasender_webhook', 'id', $webhook->getId(), $webhook->getAllData());
        if ($id) {
            $webhook->setId($id);
        }
    }

    public function getWebhookById(int $id): ?array
    {
        return $this->selectSingleRow('whatsapp_wasender_webhook', 'id', $id);
    }
}
