<?php

namespace Saade\Cep\Providers;

use Exception;
use Saade\Cep\DataObjects\CepResponse;
use Saade\Cep\Requests\ViaCepRequest;
use Saloon\Http\Response;

class ViaCepProvider extends Provider
{
    protected static string $request = ViaCepRequest::class;

    /**
     * @param  array  $data
     */
    protected function handleErrors(Response $response): void
    {
        if (! $response->ok()) {
            throw new Exception('Could not connect to ViaCep provider.');
        }

        if ((! $response->json()) || $response->json('erro')) {
            throw new Exception('Could not parse ViaCep provider response.');
        }
    }

    protected function toDTO(Response $response): CepResponse
    {
        return new CepResponse(
            provider: 'viacep',
            cep: str_replace('-', '', $response->json('cep')),
            street: $response->json('logradouro'),
            neighborhood: $response->json('bairro'),
            city: $response->json('localidade'),
            state: $response->json('uf'),
        );
    }
}
