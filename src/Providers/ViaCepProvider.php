<?php

namespace Saade\Cep\Providers;

use Exception;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Saade\Cep\DataObjects\CepResponse;

class ViaCepProvider extends Provider
{
    protected function request(string $cep): RequestInterface
    {
        return new Request('GET', "https://viacep.com.br/ws/{$cep}/json/", ['Accept' => 'application/json']);
    }

    /**
     * @throws Exception
     */
    protected function handleErrors(ResponseInterface $response): void
    {
        if ($response->getStatusCode() !== 200) {
            throw new Exception('Could not connect to ViaCep provider.');
        }

        $data = $this->json($response);

        if (! empty($data['erro']) || empty($data['cep'])) {
            throw new Exception('Could not parse ViaCep provider response.');
        }
    }

    protected function toDTO(ResponseInterface $response): CepResponse
    {
        $data = $this->json($response);

        return new CepResponse(
            provider: 'viacep',
            cep: str_replace('-', '', $data['cep']),
            street: $data['logradouro'] ?? null,
            neighborhood: $data['bairro'] ?? null,
            city: $data['localidade'] ?? null,
            state: $data['uf'] ?? null,
        );
    }
}
