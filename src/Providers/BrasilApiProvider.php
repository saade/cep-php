<?php

namespace Saade\Cep\Providers;

use Exception;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Saade\Cep\DataObjects\CepResponse;

class BrasilApiProvider extends Provider
{
    protected function request(string $cep): RequestInterface
    {
        return new Request('GET', "https://brasilapi.com.br/api/cep/v1/{$cep}", ['Accept' => 'application/json']);
    }

    /**
     * @throws Exception
     */
    protected function handleErrors(ResponseInterface $response): void
    {
        if ($response->getStatusCode() !== 200) {
            throw new Exception('Could not connect to Brasil Api provider.');
        }

        if (empty($this->json($response)['cep'])) {
            throw new Exception('Could not parse Brasil Api provider response.');
        }
    }

    protected function toDTO(ResponseInterface $response): CepResponse
    {
        $data = $this->json($response);

        return new CepResponse(
            provider: 'brasil-api',
            cep: $data['cep'],
            street: $data['street'] ?? null,
            neighborhood: $data['neighborhood'] ?? null,
            city: $data['city'] ?? null,
            state: $data['state'] ?? null,
        );
    }
}
