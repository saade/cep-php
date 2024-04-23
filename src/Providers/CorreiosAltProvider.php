<?php

namespace Saade\Cep\Providers;

use Exception;
use Saade\Cep\DataObjects\CepResponse;
use Saade\Cep\Requests\CorreiosAltRequest;
use Saloon\Http\Response;

class CorreiosAltProvider extends Provider
{
    protected static string $request = CorreiosAltRequest::class;

    /**
     * @param  array  $data
     * @throws Exception
     */
    protected function handleErrors(Response $response): void
    {
        if (! $response->ok()) {
            throw new Exception('Could not connect to Correios provider.');
        }

        if ($response->json('erro')) {
            throw new Exception('Could not parse Correios-alt provider response.');
        }
    }

    /**
     * @param  array  $data
     */
    protected function toDTO(Response $response): CepResponse
    {
        return new CepResponse(
            provider: 'correios-alt',
            cep: $response->json('dados.0.cep'),
            street: $response->json('dados.0.logradouroDNEC'),
            neighborhood: $response->json('dados.0.bairro'),
            city: $response->json('dados.0.localidade'),
            state: $response->json('dados.0.uf'),
        );
    }
}
