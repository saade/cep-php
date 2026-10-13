<?php

namespace Saade\Cep\Providers;

use Exception;
use Saade\Cep\DataObjects\CepResponse;
use Saade\Cep\Requests\OpenCepRequest;
use Saloon\Http\Response;

class OpenCepProvider extends Provider
{
    protected static string $request = OpenCepRequest::class;

    /**
     * @throws Exception
     */
    protected function handleErrors(Response $response): void
    {
        if (! $response->ok()) {
            throw new Exception('Could not connect to OpenCEP provider.');
        }

        if (! $response->json('cep')) {
            throw new Exception('Could not parse OpenCEP provider response.');
        }
    }

    protected function toDTO(Response $response): CepResponse
    {
        return new CepResponse(
            provider: 'open-cep',
            cep: preg_replace('/\D/', '', $response->json('cep')),
            street: $response->json('logradouro'),
            neighborhood: $response->json('bairro'),
            city: $response->json('localidade'),
            state: $response->json('uf'),
        );
    }
}
