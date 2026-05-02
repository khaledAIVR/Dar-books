<?php

namespace App\Mail\Transport;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Mail\Transport\Transport;
use Swift_Mime_Attachment;
use Swift_Mime_SimpleMessage;
use Swift_TransportException;

/**
 * Sends mail via Brevo (Sendinblue) REST API — no SMTP connection.
 *
 * @see https://developers.brevo.com/reference/sendtransacemail
 */
class BrevoTransport extends Transport
{
    /** @var ClientInterface */
    protected $client;

    /** @var string */
    protected $apiKey;

    public function __construct(ClientInterface $client, string $apiKey)
    {
        if ($apiKey === '') {
            throw new \InvalidArgumentException('Brevo API key is empty; set BREVO_API_KEY.');
        }
        $this->client = $client;
        $this->apiKey = $apiKey;
    }

    /**
     * {@inheritdoc}
     */
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $this->beforeSendPerformed($message);

        $payload = $this->buildPayload($message);

        try {
            $this->client->request('POST', 'https://api.brevo.com/v3/smtp/email', [
                'headers' => [
                    'api-key' => $this->apiKey,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
                'http_errors' => true,
            ]);
        } catch (GuzzleException $e) {
            throw new Swift_TransportException('Brevo API request failed: '.$e->getMessage(), 0, $e);
        }

        $this->sendPerformed($message);

        return $this->numberOfRecipients($message);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildPayload(Swift_Mime_SimpleMessage $message): array
    {
        $from = $message->getFrom() ?: [];
        if ($from === []) {
            throw new Swift_TransportException('Brevo: message has no From address.');
        }
        $fromEmail = (string) key($from);
        $fromName = (string) ($from[$fromEmail] ?? '');

        $to = $this->formatAddressList($message->getTo() ?: []);
        if ($to === []) {
            throw new Swift_TransportException('Brevo: message has no recipients.');
        }

        [$html, $text] = $this->extractBodies($message);
        if ($html === null && $text === null) {
            throw new Swift_TransportException('Brevo: message has no HTML or plain text body.');
        }

        $payload = [
            'sender' => [
                'email' => $fromEmail,
                'name' => $fromName,
            ],
            'to' => $to,
            'subject' => (string) ($message->getSubject() ?? ''),
        ];

        if ($html !== null) {
            $payload['htmlContent'] = $html;
        }
        if ($text !== null) {
            $payload['textContent'] = $text;
        }

        $cc = $this->formatAddressList($message->getCc() ?: []);
        if ($cc !== []) {
            $payload['cc'] = $cc;
        }

        $bcc = $this->formatAddressList($message->getBcc() ?: []);
        if ($bcc !== []) {
            $payload['bcc'] = $bcc;
        }

        $replyTo = $message->getReplyTo() ?: [];
        if ($replyTo !== []) {
            $replyEmail = (string) key($replyTo);
            $payload['replyTo'] = [
                'email' => $replyEmail,
                'name' => (string) ($replyTo[$replyEmail] ?? ''),
            ];
        }

        return $payload;
    }

    /**
     * @param  array<string, string|null>  $addresses
     * @return array<int, array{email: string, name: string}>
     */
    protected function formatAddressList(array $addresses): array
    {
        $out = [];
        foreach ($addresses as $email => $name) {
            $out[] = [
                'email' => (string) $email,
                'name' => (string) ($name ?? ''),
            ];
        }

        return $out;
    }

    /**
     * @return array{0: ?string, 1: ?string} [html, text]
     */
    protected function extractBodies(Swift_Mime_SimpleMessage $message): array
    {
        $html = null;
        $text = null;

        $rootType = strtolower((string) $message->getContentType());
        if (strpos($rootType, 'text/html') !== false) {
            $html = (string) $message->getBody();
        } elseif (strpos($rootType, 'text/plain') !== false) {
            $text = (string) $message->getBody();
        }

        foreach ($message->getChildren() as $part) {
            if ($part instanceof Swift_Mime_Attachment) {
                continue;
            }
            $ct = strtolower((string) $part->getContentType());
            if (strpos($ct, 'text/html') !== false) {
                $html = (string) $part->getBody();
            }
            if (strpos($ct, 'text/plain') !== false) {
                $text = (string) $part->getBody();
            }
        }

        if ($html === null && $text === null) {
            $raw = $message->getBody();
            if (is_string($raw) && $raw !== '') {
                $text = $raw;
            }
        }

        return [$html, $text];
    }
}
