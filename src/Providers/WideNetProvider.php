<?php

namespace Saade\Cep\Providers;

use Exception;
use Saade\Cep\DataObjects\CepResponse;
use Saade\Cep\Requests\WideNetRequest;
use Saloon\Http\Response;

class WideNetProvider extends Provider
{
    protected static string $request = WideNetRequest::class;

    /**
     * @throws Exception
     */
    protected function handleErrors(Response $response): void
    {
        if (! $response->ok()) {
            throw new Exception('Could not connect to WideNet provider.');
        }

        if ($response->json('ok') === false || $response->json('status') !== 200 || ! $response->json('code')) {
            throw new Exception('Could not parse WideNet provider response.');
        }
    }

    protected function toDTO(Response $response): CepResponse
    {
        return new CepResponse(
            provider: 'widenet',
            cep: str_replace('-', '', $response->json('code')),
            street: $response->json('address'),
            neighborhood: $response->json('district'),
            city: $response->json('city'),
            state: $response->json('state'),
        );
    }
}
