<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper around the Cloudflare Gemma 4 worker.
 *
 * Usage:
 *   $reply = app(GemmaService::class)->chat([
 *       ['role' => 'user', 'content' => 'Summarise this order…'],
 *   ]);
 */
final class GemmaService
{
    private string $workerUrl;
    private string $token;

    public function __construct()
    {
        $this->workerUrl = rtrim((string) config('services.gemma.worker_url'), '/');
        $this->token     = (string) config('services.gemma.token');
    }

    /**
     * Send a conversation and return the assistant reply.
     *
     * @param  array<array{role: string, content: string}>  $messages
     *
     * @throws \RuntimeException|\Illuminate\Http\Client\RequestException
     */
    public function chat(array $messages): string
    {
        if ($this->workerUrl === '') {
            throw new \RuntimeException('GEMMA_WORKER_URL is not configured.');
        }

        $request = Http::timeout(30)
            ->withHeaders(['Content-Type' => 'application/json']);

        if ($this->token !== '') {
            $request = $request->withToken($this->token);
        }

        $response = $request->post("{$this->workerUrl}/chat", [
            'messages' => $messages,
        ]);

        $response->throw();

        return (string) ($response->json('reply') ?? '');
    }
}
