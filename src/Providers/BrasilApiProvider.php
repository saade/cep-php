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
     */
    protected static function handleErrors(Response $response, mixed $data): void
    {
        if (! $response->ok()) {
            throw new Exception('Could not connect to Brasil Api provider.');
        }

        if (! $data) {
            throw new Exception('Could not parse Brasil Api provider response.');
        }
    }

    /**
     * @param  array  $data
     */
    protected static function mapResponse(Response $response, mixed $data): ?CepResponse
    {
        return new CepResponse(
            cep: data_get($data, 'cep'),
            state: data_get($data, 'state'),
            city: data_get($data, 'city'),
            neighborhood: data_get($data, 'neighborhood'),
            street: data_get($data, 'street'),
            provider: 'brasil-api',
        );
    }
}
