<?php

namespace Saade\Cep\Providers;

use Exception;
use Saade\Cep\DataObjects\CepResponse;
use Saade\Cep\Requests\BrasilApiRequest;
use Saloon\Http\Response;

class BrasilApiProvider extends Provider
{
    protected static string $request = BrasilApiRequest::class;

    /**
     * @param  array  $data
     *
     * @throws Exception
     */
    protected function handleErrors(Response $response): void
    {
        if (! $response->ok()) {
            throw new Exception('Could not connect to Brasil Api provider.');
        }

        if (! $response->json()) {
            throw new Exception('Could not parse Brasil Api provider response.');
        }
    }

    /**
     * @param  array  $data
     */
    protected function toDTO(Response $response): CepResponse
    {
        return new CepResponse(
            provider: 'brasil-api',
            cep: $response->json('cep'),
            street: $response->json('street'),
            neighborhood: $response->json('neighborhood'),
            city: $response->json('city'),
            state: $response->json('state'),
        );
    }
}
