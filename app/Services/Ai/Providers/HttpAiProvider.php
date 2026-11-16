<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\Contracts\AiProvider;
use App\Services\Ai\Exceptions\AiProviderException;
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
