<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\Contracts\AiProvider;
use App\Services\Ai\Exceptions\AiProviderException;
use Generator;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

abstract class HttpAiProvider implements AiProvider
{
    public function __construct(protected string $baseUrl) {}

    protected function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)->acceptJson()->asJson()->connectTimeout(10)->timeout(90);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws AiProviderException
     */
    protected function send(PendingRequest $client, string $path, array $payload): array
    {
        return $this->parse(fn (): Response => $client->post($path, $payload));
    }

    /**
     * Posts the payload and yields the JSON-decoded `data:` events of the
     * server-sent event stream. Providers map each event to a text chunk.
     *
     * @param  array<string, mixed>  $payload
     * @return Generator<int, string>
     *
     * @throws AiProviderException
     */
    protected function streamData(PendingRequest $client, string $path, array $payload): Generator
    {
        try {
            $response = $client->withOptions(['stream' => true])->post($path, $payload);
        } catch (ConnectionException) {
            throw AiProviderException::unreachable();
        }

        if ($response->failed()) {
            throw AiProviderException::fromStatus($response->status());
        }

        $body = $response->toPsrResponse()->getBody();
        $buffer = '';

        while (! $body->eof()) {
            $buffer .= $body->read(1024);

            while (($newline = strpos($buffer, "\n")) !== false) {
                $line = rtrim(substr($buffer, 0, $newline), "\r");
                $buffer = substr($buffer, $newline + 1);

                if (str_starts_with($line, 'data:') && trim(substr($line, 5)) !== '') {
                    yield trim(substr($line, 5));
                }
            }
        }

        $tail = trim($buffer);

        if (str_starts_with($tail, 'data:') && trim(substr($tail, 5)) !== '') {
            yield trim(substr($tail, 5));
        }
    }

    /**
     * Forwards the non-empty chunks and fails if the vendor produced none.
     *
     * @param  Generator<int, string>  $chunks
     * @return Generator<int, string>
     *
     * @throws AiProviderException
     */
    protected function requireChunks(Generator $chunks): Generator
    {
        $sent = false;

        foreach ($chunks as $chunk) {
            if ($chunk !== '') {
                $sent = true;

                yield $chunk;
            }
        }

        if (! $sent) {
            throw AiProviderException::emptyResponse();
        }
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     *
     * @throws AiProviderException
     */
    protected function fetch(PendingRequest $client, string $path, array $query = []): array
    {
        return $this->parse(fn (): Response => $client->get($path, $query));
    }

    /**
     * @param  list<string>  $ids
     * @return list<string>
     */
    protected function sortedUnique(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids, fn (string $id): bool => $id !== '')));
        sort($ids);

        return $ids;
    }

    /**
     * @param  callable(): Response  $request
     * @return array<string, mixed>
     *
     * @throws AiProviderException
     */
    private function parse(callable $request): array
    {
        try {
            $response = $request();
        } catch (ConnectionException) {
            throw AiProviderException::unreachable();
        }

        if ($response->failed()) {
            throw AiProviderException::fromStatus($response->status());
        }

        return $response->json() ?? [];
    }
}
