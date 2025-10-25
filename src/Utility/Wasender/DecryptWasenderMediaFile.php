<?php

namespace Pantono\Messaging\Utility\Wasender;

use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Modified from https://wasenderapi.com/help/messaging/handling-media-message-from-the-webhook-event-message-upsert
 */
class DecryptWasenderMediaFile
{
    /**
     * @return array<string, string>
     */
    public static function decryptFileFromMessageObject(ParameterBag $messageObject): array
    {
        $mediaType = 'image';
        $filename = $messageObject->get('id');
        if ($messageObject->has('imageMessage')) {
            $messageDataObject = new ParameterBag($messageObject->get('imageMessage'));
            $extension = 'jpg';
        } elseif ($messageObject->has('stickerMessage')) {
            $messageDataObject = new ParameterBag($messageObject->get('stickerMessage'));
            $extension = 'jpg';
        } elseif ($messageObject->has('videoMessage')) {
            $mediaType = 'video';
            $messageDataObject = new ParameterBag($messageObject->get('videoMessage'));
            $extension = 'mp4';
        } elseif ($messageObject->has('audioMessage')) {
            $messageDataObject = new ParameterBag($messageObject->get('audioMessage'));
            $mediaType = 'audio';
            $extension = 'ogg';
        } elseif ($messageObject->has('documentMessage')) {
            $messageDataObject = new ParameterBag($messageObject->get('documentMessage'));
            $mediaType = 'document';
            $path = pathinfo($messageDataObject->get('fileName'));
            $extension = $path['extension'] ?? 'txt';
        } else {
            throw new \RuntimeException('Unsupported message type');
        }

        $mediaKey = $messageDataObject->get('mediaKey');
        $url = $messageDataObject->get('url');

        $decryptedContents = self::decryptWhatsAppMedia($mediaKey, $url, $mediaType);
        return [
            'contents' => $decryptedContents,
            'filename' => $filename . '.' . $extension,
        ];
    }

    private static function downloadFile(string $url): string
    {
        $context = stream_context_create([
            "http" => [
                "follow_location" => true,
            ]
        ]);
        return file_get_contents($url, false, $context);
    }

    private static function getDecryptionKeys(string $mediaKey, string $type = 'image', int $length = 112): string
    {
        $info = match ($type) {
            'image' => 'WhatsApp Image Keys',
            'video' => 'WhatsApp Video Keys',
            'audio' => 'WhatsApp Audio Keys',
            'document' => 'WhatsApp Document Keys',
            default => throw new \Exception("Invalid media type"),
        };

        return hash_hkdf('sha256', base64_decode($mediaKey), $length, $info, '');
    }

    private static function decryptWhatsAppMedia(string $mediaKey, string $url, string $mediaType = 'image'): string
    {
        $encFile = self::downloadFile($url);
        if (!$encFile) {
            throw new \Exception("Failed to download file");
        }
        $keys = self::getDecryptionKeys($mediaKey, $mediaType);
        $iv = substr($keys, 0, 16);
        $cipherKey = substr($keys, 16, 32);
        $ciphertext = substr($encFile, 0, strlen($encFile) - 10);

        $plaintext = openssl_decrypt($ciphertext, 'aes-256-cbc', $cipherKey, OPENSSL_RAW_DATA, $iv);
        if (!$plaintext) {
            throw new \Exception("Failed to decrypt media");
        }
        return $plaintext;
    }
}
