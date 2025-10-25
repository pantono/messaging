<?php

namespace Pantono\Messaging\Service;

use GuzzleHttp\Client;
use Pantono\Hydrator\Hydrator;
use Pantono\Logger\Factory\LoggedHttpClientFactory;
use Pantono\Messaging\Event\Wasender\PostWasenderWebhookSaveEvent;
use Pantono\Messaging\Event\Wasender\PreWasenderWebhookSaveEvent;
use Pantono\Messaging\Model\Wasender\WasenderWebhook;
use Pantono\Messaging\Repository\WasenderRepository;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\ParameterBag;
use Pantono\Messaging\Model\WhatsappInstance;

class WasenderService implements WhatsappServiceInterface
{
    private string $apiKey;
    private string $personalToken;
    private ?string $webhookSecret;
    private string $baseUrl;
    private WhatsappInstance $instance;
    private ?Client $client = null;
    private WasenderRepository $repository;
    private EventDispatcher $dispatcher;
    private Hydrator $hydrator;

    public function __construct(WasenderRepository $repository, EventDispatcher $dispatcher, Hydrator $hydrator)
    {
        $this->repository = $repository;
        $this->dispatcher = $dispatcher;
        $this->hydrator = $hydrator;
    }

    public function setInstance(WhatsappInstance $instance): void
    {
        $this->instance = $instance;
        $this->apiKey = $instance->getMetaValue('apiKey');
        $this->personalToken = $instance->getMetaValue('personalAccessToken');
        $this->webhookSecret = $instance->getMetaValue('webhookSecret');
        $this->baseUrl = rtrim($instance->getMetaValue('baseUrl') ?: 'https://wasenderapi.com/api', '/');
    }

    public function getAllGroups(): array
    {
        return $this->get('/groups');
    }

    public function getGroupMetadata(string $id): array
    {
        return $this->get('/groups/' . $id . '/metadata');
    }

    public function getWebhookById(int $id): ?WasenderWebhook
    {
        return $this->hydrator->hydrate(WasenderWebhook::class, $this->repository->getWebhookById($id));
    }

    public function saveWebhook(WasenderWebhook $webhook): void
    {
        $previous = $webhook->getId() ? $this->getWebhookById($webhook->getId()) : null;
        $event = new PreWasenderWebhookSaveEvent();
        $event->setCurrent($webhook);
        $event->setPrevious($previous);
        $this->dispatcher->dispatch($event);

        $this->repository->saveWebhook($webhook);

        $event = new PostWasenderWebhookSaveEvent();
        $event->setCurrent($webhook);
        $event->setPrevious($previous);
        $this->dispatcher->dispatch($event);
    }

    public function verifyWebhook(WasenderWebhook $webhook): bool
    {
        $headers = new ParameterBag($webhook->getHeaders());
        $sig = $headers->get('X-Webhook-Signature');
        return $sig === $this->webhookSecret;
    }

    /**
     * @return array<string,mixed>
     */
    public function deleteMessage(string $messageId): array
    {
        return $this->delete('/messages/' . $messageId);
    }

    /**
     * @return array<string,mixed>
     */
    public function sendText(string $to, string $message, ?string $replyTo = null): array
    {
        return $this->post('/send-message', [
            'to' => $to,
            'text' => $message,
            'replyTo' => $replyTo
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    public function sendImage(string $to, string $imageUrl, ?string $text = null, ?string $replyTo = null): array
    {
        return $this->post('/send-message', [
            'to' => $to,
            'imageUrl' => $imageUrl,
            'text' => $text,
            'replyTo' => $replyTo
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    public function sendVideo(string $to, string $videoUrl, ?string $text = null, ?string $replyTo = null): array
    {
        return $this->post('/send-message', [
            'to' => $to,
            'videoUrl' => $videoUrl,
            'text' => $text,
            'replyTo' => $replyTo
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    public function sendAudio(string $to, string $audioUrl, ?string $text = null, ?string $replyTo = null): array
    {
        return $this->post('/send-message', [
            'to' => $to,
            'audioUrl' => $audioUrl,
            'text' => $text,
            'replyTo' => $replyTo
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    public function sendSticker(string $to, string $stickerUrl, ?string $text = null, ?string $replyTo = null): array
    {
        return $this->post('/send-message', [
            'to' => $to,
            'stickerUrl' => $stickerUrl,
            'text' => $text,
            'replyTo' => $replyTo
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    public function sendDocument(string $to, string $documentUrl, ?string $fileName = null, ?string $text = null, ?string $replyTo = null): array
    {
        return $this->post('/send-message', [
            'to' => $to,
            'documentUrl' => $documentUrl,
            'text' => $text,
            'fileName' => $fileName,
            'replyTo' => $replyTo
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    public function sendContact(string $to, array $contact, ?string $replyTo = null): array
    {
        return $this->post('/send-message', [
            'to' => $to,
            'contact' => $contact,
            'replyTo' => $replyTo
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    public function sendLocation(string $to, float $latitude, float $longitude, ?string $name = null, ?string $address = null, ?string $replyTo = null): array
    {
        return $this->post('/send-message', [
            'to' => $to,
            'replyTo' => $replyTo,
            'location' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'name' => $name,
                'address' => $address
            ],
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    public function listGroups(): array
    {
        return $this->get('/groups');
    }

    /**
     * @return array<string,mixed>
     */
    public function createGroup(string $subject, array $participants): array
    {
        return $this->post('/groups', [
            'name' => $subject,
            'participants' => $participants,
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    public function addGroupParticipants(string $groupId, array $participants): array
    {
        return $this->post('/groups/' . $groupId . '/participants/add', [
            'participants' => $participants,
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    public function removeGroupParticipants(string $groupId, array $participants): array
    {
        return $this->post('/groups/' . $groupId . '/participants/remove', [
            'participants' => $participants,
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    public function getContact(string $phone): array
    {
        return $this->get('/contacts/' . $this->e($phone));
    }

    /**
     * @return array<string,mixed>
     */
    public function blockContact(string $phone): array
    {
        return $this->post('/contacts/' . $phone . '/block');
    }

    /**
     * @return array<string,mixed>
     */
    public function unblockContact(string $phone): array
    {
        return $this->post('/contacts/' . $phone . '/unblock');
    }

    /**
     * @return array<string,string>
     */
    private function headers(): array
    {
        return [
            'X-API-KEY' => $this->personalToken,
            'Authorization' => 'Bearer ' . $this->apiKey,
            'User-Agent' => 'Pantono/1.0.0'
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function get(string $path, array $query = []): array
    {
        $url = $this->baseUrl . $path;
        if ($query) {
            $url .= '?' . http_build_query($query);
        }
        return $this->request('GET', $url);
    }

    /**
     * @return array<string,mixed>
     */
    private function post(string $path, array $body = []): array
    {
        $url = $this->baseUrl . $path;
        return $this->request('POST', $url, $body);
    }

    /**
     * @return array<string,mixed>
     */
    private function delete(string $path, array $body = []): array
    {
        $url = $this->baseUrl . $path;
        return $this->request('DELETE', $url, $body);
    }

    /**
     * @return array<string,mixed>
     */
    private function request(string $method, string $url, array $body = []): array
    {
        $params = [
            'headers' => $this->headers()
        ];
        if ($method === 'POST' || $method === 'PUT') {
            $body = $this->filterNull($body);
            $params['json'] = $body;
        }
        $response = $this->createClient()->request($method, $url, $params);

        $body = $response->getBody();
        return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    }

    private function filterNull(array $data): array
    {
        return array_filter($data, static fn($v) => $v !== null);
    }

    private function e(string $s): string
    {
        return rawurlencode($s);
    }

    private function createClient(): Client
    {
        if (!$this->client) {
            $this->client =  (new LoggedHttpClientFactory($this->instance->getMetaValue('log_name', 'wasender')))->createInstance();
        }
        return $this->client;
    }
}
