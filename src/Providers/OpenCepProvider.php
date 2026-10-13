<?php

namespace Saade\Cep\Providers;

use Exception;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Saade\Cep\DataObjects\CepResponse;

class OpenCepProvider extends Provider
{
    protected float $timeout = 3;

    protected function request(string $cep): RequestInterface
    {
        return new Request('GET', "https://opencep.com/v1/{$cep}", ['Accept' => 'application/json']);
    }

    /**
     * @throws Exception
     */
    protected function handleErrors(ResponseInterface $response): void
    {
        if ($response->getStatusCode() !== 200) {
            throw new Exception('Could not connect to OpenCEP provider.');
        }

        if (empty($this->json($response)['cep'])) {
            throw new Exception('Could not parse OpenCEP provider response.');
        }
    }

    protected function toDTO(ResponseInterface $response): CepResponse
    {
        $data = $this->json($response);

        return new CepResponse(
            provider: 'open-cep',
            cep: preg_replace('/\D/', '', $data['cep']),
            street: $data['logradouro'] ?? null,
            neighborhood: $data['bairro'] ?? null,
            city: $data['localidade'] ?? null,
            state: $data['uf'] ?? null,
        );
    }
}
